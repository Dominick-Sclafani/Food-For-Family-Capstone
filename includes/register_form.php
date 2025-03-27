<!-- Registration Form (Initially Hidden) -->
<div id="register-form-container" class="form-container mt-3" style="display: none;">
    <h2>Register</h2>
    <form id="register-form" method="POST" action="auth.php">
        <input type="hidden" name="action" value="register">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="password" required>
            <small id="password-requirements" class="form-text text-muted">
                Password must:
                <ul>
                    <li id="length" class="text-danger">Be at least 6 characters long</li>
                    <li id="uppercase" class="text-danger">Contain at least one uppercase letter</li>
                    <li id="lowercase" class="text-danger">Contain at least one lowercase letter</li>
                    <li id="number" class="text-danger">Contain at least one number</li>
                </ul>
            </small>
            <small id="password-error" class="text-danger" style="display: none;">
                Password does not meet the requirements.
            </small>
        </div>
        <input type="hidden" name="role" value="regular">
        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
</div>
