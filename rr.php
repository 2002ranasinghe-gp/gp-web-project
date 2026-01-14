case 'settings':

    $username = $_SESSION['reception'];

    // Update settings
    if(isset($_POST['update_settings'])){
        $email   = $_POST['email'];
        $contact = $_POST['contact'];

        $con->query("UPDATE receptiontb 
                     SET email='$email', contact='$contact' 
                     WHERE username='$username'");

        echo "<script>alert('Profile updated successfully');</script>";
    }

    // Change password
    if(isset($_POST['change_password'])){
        $newpass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        $con->query("UPDATE receptiontb 
                     SET password='$newpass' 
                     WHERE username='$username'");

        echo "<script>alert('Password changed successfully');</script>";
    }

    $data = $con->query("SELECT * FROM receptiontb WHERE username='$username'")->fetch_assoc();

    echo "
    <div class='dashboard-header'>
        <h1><i class='fas fa-cog mr-3'></i>Reception Settings</h1>
        <p>Manage your account details</p>
    </div>

    <div class='row'>
        <div class='col-md-6'>
            <div class='table-container'>
                <h4 class='mb-4'>Profile Information</h4>
                <form method='post'>
                    <div class='form-group'>
                        <label>Username</label>
                        <input type='text' class='form-control' value='{$data['username']}' readonly>
                    </div>
                    <div class='form-group'>
                        <label>Email</label>
                        <input type='email' name='email' class='form-control' value='{$data['email']}'>
                    </div>
                    <div class='form-group'>
                        <label>Contact</label>
                        <input type='text' name='contact' class='form-control' value='{$data['contact']}'>
                    </div>
                    <button name='update_settings' class='btn btn-primary btn-block'>
                        <i class='fas fa-save mr-1'></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div class='col-md-6'>
            <div class='table-container'>
                <h4 class='mb-4'>Change Password</h4>
                <form method='post'>
                    <div class='form-group'>
                        <label>New Password</label>
                        <input type='password' name='new_password' class='form-control' required>
                    </div>
                    <button name='change_password' class='btn btn-warning btn-block'>
                        <i class='fas fa-key mr-1'></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
    ";
    break;
