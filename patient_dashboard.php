<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "myhmsdb");

if(!$con){
    die("Database connection failed");
}

if(isset($_POST['prescription_id'])){
    $prescription_id = mysqli_real_escape_string($con, $_POST['prescription_id']);
    
    $query = "SELECT p.*, pt.fname, pt.lname, pt.email, pt.contact 
              FROM prestb p 
              JOIN patreg pt ON p.national_id = pt.national_id 
              WHERE p.id='$prescription_id'";
    
    $result = mysqli_query($con, $query);
    
    if(mysqli_num_rows($result) > 0){
        $prescription = mysqli_fetch_assoc($result);
        
        echo '
        <div class="prescription-details">
            <div class="row">
                <div class="col-md-6">
                    <h5>Patient Information</h5>
                    <p><strong>Name:</strong> ' . htmlspecialchars($prescription['fname'] . ' ' . $prescription['lname']) . '</p>
                    <p><strong>Email:</strong> ' . htmlspecialchars($prescription['email']) . '</p>
                    <p><strong>Contact:</strong> ' . htmlspecialchars($prescription['contact']) . '</p>
                </div>
                <div class="col-md-6">
                    <h5>Prescription Details</h5>
                    <p><strong>Date:</strong> ' . date('d M Y', strtotime($prescription['appdate'])) . '</p>
                    <p><strong>Doctor:</strong> Dr. ' . htmlspecialchars($prescription['doctor']) . '</p>
                    <p><strong>Status:</strong> ' . htmlspecialchars($prescription['emailStatus']) . '</p>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Medical Information</h5>
                    <p><strong>Disease:</strong> ' . htmlspecialchars($prescription['disease']) . '</p>
                    <p><strong>Allergy:</strong> ' . htmlspecialchars($prescription['allergy']) . '</p>
                </div>
                <div class="col-md-6">
                    <h5>Prescription</h5>
                    <div class="prescription-content" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        ' . nl2br(htmlspecialchars($prescription['prescription'])) . '
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="text-center mt-3">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print mr-2"></i>Print Prescription
                </button>
            </div>
        </div>';
    } else {
        echo '<div class="alert alert-danger">Prescription not found!</div>';
    }
}

mysqli_close($con);
?>