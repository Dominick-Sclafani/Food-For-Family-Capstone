<!-- Login Form (Initially Hidden) -->
<div id="login-form-container" class="form-container mt-3" style="display: none;">
    <h2>Login</h2>
    <form id="login-form" method="POST" action="auth.php">
        <input type="hidden" name="action" value="login">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Login</button>
    </form>
</div>
