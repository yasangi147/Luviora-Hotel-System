<?php
/**
 * Login and Register Modals
 * Reusable component for all pages
 */
?>

<!-- Login Modal -->
<div class="modal fade" id="login" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content login-content">
      <div class="login-image">
        <img src="images/luvioralogoblack.png" alt="image" />
      </div>
      <h3>Hello! Sign into your account</h3>
      <form id="loginForm">
        <div class="form-group">
          <input type="email" id="login-email" placeholder="Enter email address" required />
        </div>
        <div class="form-group">
          <input type="password" id="login-password" placeholder="Enter password" required />
        </div>
        <div class="form-group form-checkbox">
          <input type="checkbox" id="login-remember" /> Remember Me
          <a href="#">Forgot password?</a>
        </div>
      </form>
      <div class="form-btn">
        <a href="#" class="btn btn-orange" id="loginBtn">LOGIN</a>
        <p>Need an Account?<a href="#" data-bs-toggle="modal" data-bs-target="#register" data-bs-dismiss="modal"> Create your Luviora account</a></p>
      </div>
    </div>
  </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="register" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content login-content">
      <div class="login-image">
        <img src="images/luvioralogoblack.png" alt="image" />
      </div>
      <h3>Awesome! Create a Luviora Account</h3>
      <form id="registerForm">
        <div class="form-group">
          <input type="text" id="register-name" placeholder="Enter your name" required />
        </div>
        <div class="form-group">
          <input type="email" id="register-email" placeholder="Enter email address" required />
        </div>
        <div class="form-group">
          <input type="text" id="register-phone" placeholder="Enter phone number" />
        </div>
        <div class="form-group">
          <input type="password" id="register-password" placeholder="Enter password" required />
        </div>
        <div class="form-group">
          <input type="password" id="register-confirm" placeholder="Confirm password" required />
        </div>
      </form>
      <div class="form-btn">
        <a href="#" class="btn btn-orange" id="registerBtn">SIGN UP</a>
        <p>Already have an account?<a href="#" data-bs-toggle="modal" data-bs-target="#login" data-bs-dismiss="modal"> Login here</a></p>
      </div>
      <ul class="social-links">
        <li>
          <a href="#"><i class="fab fa-facebook" aria-hidden="true"></i></a>
        </li>
        <li>
          <a href="#"><i class="fab fa-twitter" aria-hidden="true"></i></a>
        </li>
        <li>
          <a href="#"><i class="fab fa-instagram" aria-hidden="true"></i></a>
        </li>
        <li>
          <a href="#"><i class="fab fa-linkedin" aria-hidden="true"></i></a>
        </li>
      </ul>
    </div>
  </div>
</div>

