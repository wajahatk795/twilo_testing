
<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home | Bootstrap demo</title>
  <!--favicon-->
	<link rel="icon" href="assets/images/favicon-32x32.png" type="image/png">

  <!--plugins-->
  <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/metisMenu.min.css">
  <link rel="stylesheet" type="text/css" href="assets/plugins/metismenu/mm-vertical.css">
  <!--bootstrap css-->
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">
  <!--main css-->
  <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
  <link href="sass/main.css" rel="stylesheet">
  <link href="sass/dark-theme.css" rel="stylesheet">
  <link href="sass/responsive.css" rel="stylesheet">

  </head>

  <body class="bg-register">


    <!--authentication-->

     <div class="container-fluid my-5">
        <div class="row">
           <div class="col-12 col-md-8 col-lg-6 col-xl-5 col-xxl-5 mx-auto">
            <div class="card rounded-4">
              <div class="card-body p-5">
                  <img src="assets/images/logo1.png" class="mb-4" width="145" alt="">

                  @if(isset($user_registered) && $user_registered)
                    <!-- Show Company Creation Form -->
                    <h4 class="fw-bold">Create Your Company</h4>
                    <p class="mb-0">Add a company to get started</p>

                    <div class="form-body my-4">
                      <form class="row g-3" action="{{ route('auth.register.company') }}" method="POST">
                        @csrf
                        <div class="col-12">
                          <label for="companyName" class="form-label">Company Name</label>
                          <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="companyName" name="company_name" placeholder="Your Company" value="{{ old('company_name') }}" required>
                          @error('company_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <label for="planSelect" class="form-label">Plan</label>
                          <select class="form-select @error('plan') is-invalid @enderror" id="planSelect" name="plan" required>
                            <option value="">Select Your Plan</option>
                            <option value="Free" {{ old('plan') == 'Free' ? 'selected' : '' }}>Free</option>
                            <option value="Pro" {{ old('plan') == 'Pro' ? 'selected' : '' }}>Pro</option>
                            <option value="Enterprise" {{ old('plan') == 'Enterprise' ? 'selected' : '' }}>Enterprise</option>
                          </select>
                          @error('plan')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <label class="form-label">Questions (Optional)</label>
                          <div id="questions-container">
                            <div class="question-row mb-2" style="display: flex; gap: 8px;">
                              <input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />
                              <button type="button" class="btn btn-sm btn-danger remove-question" style="display: none;">Remove</button>
                            </div>
                          </div>
                          <div style="text-align: end">
                            <button type="button" id="add-question" class="btn btn-sm btn-secondary mt-2">+ Add Question</button>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Company</button>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="text-center">
                            <p class="mb-0">Welcome, <strong>{{ $user->name ?? 'User' }}</strong>! Create your company to continue.</p>
                          </div>
                        </div>
                      </form>
                    </div>
                  @else
                    <!-- Show User Registration Form -->
                    <h4 class="fw-bold">Get Started Now</h4>
                    <p class="mb-0">Enter your credentials to create your account</p>

                    <div class="form-body my-4">
                      <form class="row g-3" action="{{ route('auth.register.post') }}" method="POST">
                        @csrf
                        <div class="col-12">
                          <label for="inputUsername" class="form-label">Company Name</label>
                          <input type="text" class="form-control @error('username') is-invalid @enderror" id="inputUsername" name="username" placeholder="Jhon" value="{{ old('username') }}" required>
                          @error('username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <label for="inputEmailAddress" class="form-label">Email Address</label>
                          <input type="email" class="form-control @error('email') is-invalid @enderror" id="inputEmailAddress" name="email" placeholder="example@user.com" value="{{ old('email') }}" required>
                          @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <label for="inputChoosePassword" class="form-label">Password</label>
                          <div class="input-group" id="show_hide_password">
                            <input type="password" class="form-control border-end-0 @error('password') is-invalid @enderror" id="inputChoosePassword" name="password" placeholder="Enter Password" required>
                            <a href="javascript:;" class="input-group-text bg-transparent"><i class="bi bi-eye-slash-fill"></i></a>
                          </div>
                          @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <label for="inputConfirmPassword" class="form-label">Confirm Password</label>
                          <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="inputConfirmPassword" name="password_confirmation" placeholder="Confirm Password" required>
                          @error('password_confirmation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-12">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="flexSwitchCheckChecked">
                            <label class="form-check-label" for="flexSwitchCheckChecked">I read and agree to Terms &amp; Conditions</label>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="text-start">
                            <p class="mb-0">Already have an account? <a href="{{ route('auth.login') }}">Sign in here</a></p>
                          </div>
                        </div>
                      </form>
                    </div>
                  @endif


              </div>
            </div>
           </div>
        </div><!--end row-->
     </div>
      
    <!--authentication-->




    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      $(document).ready(function () {
        $("#show_hide_password a").on('click', function (event) {
          event.preventDefault();
          if ($('#show_hide_password input').attr("type") == "text") {
            $('#show_hide_password input').attr('type', 'password');
            $('#show_hide_password i').addClass("bi-eye-slash-fill");
            $('#show_hide_password i').removeClass("bi-eye-fill");
          } else if ($('#show_hide_password input').attr("type") == "password") {
            $('#show_hide_password input').attr('type', 'text');
            $('#show_hide_password i').removeClass("bi-eye-slash-fill");
            $('#show_hide_password i').addClass("bi-eye-fill");
          }
        });
      });

      // Manage multiple questions on the register company form
      (function(){
        var container = document.getElementById('questions-container');
        var addBtn = document.getElementById('add-question');
        var form = document.querySelector('form[action*="register/company"]');

        if (container && addBtn && form) {
          // Add new question input
          addBtn.addEventListener('click', function(e){
            e.preventDefault();
            var newRow = document.createElement('div');
            newRow.className = 'question-row mb-2';
            newRow.style.display = 'flex';
            newRow.style.gap = '8px';
            newRow.innerHTML = '<input type="text" name="questions[]" class="form-control" placeholder="e.g. What is your full name?" />' +
              '<button type="button" class="btn btn-sm btn-danger remove-question">Remove</button>';
            container.appendChild(newRow);
            updateRemoveButtons();
          });

          // Remove question input
          container.addEventListener('click', function(e){
            if (e.target.classList.contains('remove-question')){
              e.preventDefault();
              e.target.closest('.question-row').remove();
              updateRemoveButtons();
            }
          });

          // Show/hide remove buttons based on number of rows
          function updateRemoveButtons(){
            var rows = container.querySelectorAll('.question-row');
            rows.forEach(function(row){
              var btn = row.querySelector('.remove-question');
              btn.style.display = rows.length > 1 ? 'block' : 'none';
            });
          }

          // Initial check
          updateRemoveButtons();
        }
      })();
    </script>

        <script>
        // Success Message
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
            });
        @endif

        // Error Message
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "{{ session('error') }}",
            });
        @endif

        // Validator Errors
        @if($errors->any())
            let errorMessages = "";
            @foreach($errors->all() as $error)
                errorMessages += "• {{ $error }}\n";
            @endforeach

            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMessages.replace(/\n/g, '<br>'),
            });
        @endif
    </script>
  
  </body>
</html>