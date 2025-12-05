<?php

// app/Http/Controllers/MediaStreamController.php
namespace App\Http\Controllers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use OpenAI\Laravel\Facades\OpenAI;

class MediaStreamController extends Controller implements MessageComponentInterface
{
    protected $clients;
    protected $openAiWs = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        // Open a Realtime session toward OpenAI
        $this->openAiWs[$conn->resourceId] = OpenAI::realtime()->connect([
            'model' => 'gpt-4o-realtime',
            'voice' => 'alloy',
            'instructions' => 'You are a friendly assistant. Ask the caller: 1. full name, 2. email, 3. date of birth. Wait for each answer. After the third answer thank them and end.'
        ]);
        // Forward OpenAI audio back to Twilio
        $this->openAiWs[$conn->resourceId]->onMessage = fn($msg) => $conn->send($msg);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Twilio binary (μ-law) or events
        $this->openAiWs[$from->resourceId]->send($msg);
        // If OpenAI sends a "response.done" event, parse answers
        $decoded = json_decode($msg, true);
        if (($decoded['type'] ?? null) === 'response.done') {
            $this->storeAnswers($from, $decoded);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->openAiWs[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    private function storeAnswers($conn, $payload)
    {
        $transcript = collect($payload['response']['output'])
            ->where('type', 'audio_transcript')
            ->pluck('transcript')
            ->implode(' ');

        // Very naive parser – good enough for MVP
        preg_match('/name.*?([A-Z][a-z]+ [A-Z][a-z]+)/i', $transcript, $n);
        preg_match('/email.*?([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/i', $transcript, $e);
        preg_match('/birth.*?([A-Z][a-z]+ \d{1,2},? \d{4})/i', $transcript, $d);

        $callSid = $conn->callSid ?? 'unknown';   // Twilio sends it in first message
        \App\Models\SurveyCall::where('call_sid', $callSid)->update([
            'name'  => $n[1] ?? null,
            'email' => $e[1] ?? null,
            'dob'   => $d[1] ?? null,
            'status'=> 'complete',
        ]);
    }
}
