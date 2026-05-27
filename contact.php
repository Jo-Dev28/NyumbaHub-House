<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Save to database
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if($stmt->execute([$name, $email, $subject, $message])) {
        // Send email notification to admin
        $to = SITE_EMAIL;
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        mail($to, "Contact Form: $subject", $message, $headers);
        
        $success = "Thank you for contacting us! We'll get back to you soon.";
    } else {
        $error = "Failed to send message. Please try again.";
    }
}

$page_title = 'Contact Us';
require_once 'includes/header.php';
?>

<style>
    .contact-info-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
    }
    .contact-info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .contact-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: white;
        font-size: 1.8rem;
    }
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h3 class="mb-3">Get In Touch</h3>
                    <p class="text-muted mb-4">Have questions about our platform? We're here to help!</p>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                            </div>
                            <div class="col-12">
                                <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                            </div>
                            <div class="col-12">
                                <textarea name="message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Visit Us</h5>
                        <p class="text-muted">Nairobi, Kenya<br>Westlands, 00100</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Call Us</h5>
                        <p class="text-muted"><?php echo SITE_PHONE; ?><br>Mon-Fri, 9am-6pm</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>Email Us</h5>
                        <p class="text-muted"><?php echo SITE_EMAIL; ?><br>Support 24/7</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="contact-info-card">
                        <div class="contact-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h5>WhatsApp</h5>
                        <p class="text-muted"><?php echo SITE_PHONE; ?><br>Chat with us</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Google Maps -->
    <div class="mt-5">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.819573124656!2d36.821921!3d-1.286389!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f10d5c5a6b9b1%3A0x8b5c5c5c5c5c5c5c!2sNairobi%20CBD!5e0!3m2!1sen!2ske!4v1640000000000!5m2!1sen!2ske" 
                width="100%" 
                height="400" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>