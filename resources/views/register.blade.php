
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
                  <h4 class="fw-bold">Get Started Now</h4>
                  <p class="mb-0">Enter your credentials to create your account</p>

                  <div class="form-body my-4">
										<form class="row g-3" action="{{ route('auth.register.post') }}" method="POST">
											@csrf
											<div class="col-12">
												<label for="inputUsername" class="form-label">Username</label>
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
												<label for="inputSelectCountry" class="form-label">Country</label>
												<select class="form-select @error('country') is-invalid @enderror" id="inputSelectCountry" name="country" required>
													<option value="">Select Country</option>
													<option value="India" {{ old('country') == 'India' ? 'selected' : '' }}>India</option>
													<option value="United Kingdom" {{ old('country') == 'United Kingdom' ? 'selected' : '' }}>United Kingdom</option>
													<option value="America" {{ old('country') == 'America' ? 'selected' : '' }}>America</option>
													<option value="Dubai" {{ old('country') == 'Dubai' ? 'selected' : '' }}>Dubai</option>
												</select>
												@error('country')
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


              </div>
            </div>
           </div>
        </div><!--end row-->
     </div>
      
    <!--authentication-->




    <!--plugins-->
    <script src="assets/js/jquery.min.js"></script>

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
    </script>
  
  </body>
</html>