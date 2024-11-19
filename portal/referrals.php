<?php
require_once "/var/www/nestogy/includes/inc_portal.php";
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3>Send a Referral</h3>
            </div>
            <div class="card-body">
                <div class="bs-stepper wizard-numbered mt-2">
                    <div class="bs-stepper-header">
                        <div class="step" data-target="#referral-info">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">1</span>
                                <span class="bs-stepper-label mt-1">
                                    <span class="bs-stepper-title">Information</span>
                                    <span class="bs-stepper-subtitle">Referral Program Details</span>
                                </span>
                            </button>
                        </div>
                        <div class="line">
                            <i class="bx bx-chevron-right"></i>
                        </div>
                        <div class="step" data-target="#message-preview">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">2</span>
                                <span class="bs-stepper-label mt-1">
                                    <span class="bs-stepper-title">Preview</span>
                                    <span class="bs-stepper-subtitle">Review Your Message</span>
                                </span>
                            </button>
                        </div>
                        <div class="line">
                            <i class="bx bx-chevron-right"></i>
                        </div>
                        <div class="step" data-target="#send-referral">
                            <button type="button" class="step-trigger">
                                <span class="bs-stepper-circle">3</span>
                                <span class="bs-stepper-label mt-1">
                                    <span class="bs-stepper-title">Send</span>
                                    <span class="bs-stepper-subtitle">Send Your Referral</span>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="bs-stepper-content">
                        <form id="referralForm" onSubmit="return false">
                            <!-- Referral Information -->
                            <div id="referral-info" class="content">
                                <div class="content-header mb-3">
                                    <h6 class="mb-0">Referral Program Information</h6>
                                    <small>Learn about our referral program.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <p>Send a referral to a friend or colleague to earn credit towards your next bill.</p>
                                        <ul>
                                            <li>Have them click the link in the email we send them to get started.</li>
                                            <li>They get 10% off their first invoice.</li>
                                            <li>You get 5% off your next 6 months of service*</li>
                                        </ul>
                                        <small>*some restrictions may apply</small>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between">
                                        <button class="btn btn-label-secondary btn-prev" disabled>
                                            <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                        </button>
                                        <button class="btn btn-primary btn-next">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-1 me-0">Next</span>
                                            <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Send Referral (moved before Message Preview) -->
                            <div id="send-referral" class="content">
                                <div class="content-header mb-3">
                                    <h6 class="mb-0">Enter Referral Details</h6>
                                    <small>Provide information about your friend.</small>
                                </div>
                                <input type="hidden" id="sender_name" value="<?php echo $contact_name; ?>" />
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="firstName">First Name</label>
                                        <input type="text" id="firstName" class="form-control" placeholder="John" />
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="lastName">Last Name</label>
                                        <input type="text" id="lastName" class="form-control" placeholder="Doe" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="emailAddress">Email Address</label>
                                        <input type="email" id="emailAddress" class="form-control" placeholder="john.doe@example.com" />
                                    </div>
                                    <div class="col-12 d-flex justify-content-between">
                                        <button class="btn btn-primary btn-prev">
                                            <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                        </button>
                                        <button class="btn btn-primary btn-next">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-1 me-0">Next</span>
                                            <i class="bx bx-chevron-right bx-sm me-sm-n2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Message Preview -->
                            <div id="message-preview" class="content">
                                <div class="content-header mb-3">
                                    <h6 class="mb-0">Message Preview</h6>
                                    <small>Review your referral message.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label" for="previewMessage">Selected Message</label>
                                        <select class="form-select" id="previewMessage" name="previewMessage">
                                            <option value="1">Message 1</option>
                                            <option value="2">Message 2</option>
                                            <option value="3">Message 3</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div id="messagePreviewText" class="alert alert-primary" role="alert">
                                            <!-- Message preview will be dynamically inserted here -->
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-between">
                                        <button class="btn btn-primary btn-prev">
                                            <i class="bx bx-chevron-left bx-sm ms-sm-n2"></i>
                                            <span class="align-middle d-sm-inline-block d-none">Previous</span>
                                        </button>
                                        <button class="btn btn-success btn-submit">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wizardNumbered = document.querySelector(".wizard-numbered");

    if (wizardNumbered) {
        const steps = wizardNumbered.querySelectorAll('.step');
        const contents = wizardNumbered.querySelectorAll('.content');
        let currentStep = 0;

        function showStep(stepIndex) {
            steps.forEach((step, index) => {
                if (index === stepIndex) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            contents.forEach((content, index) => {
                if (index === stepIndex) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
        }

        function nextStep() {
            if (currentStep < steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        }

        // Initialize first step
        showStep(currentStep);

        // Handle next button clicks
        wizardNumbered.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', nextStep);
        });

        // Handle previous button clicks
        wizardNumbered.querySelectorAll('.btn-prev').forEach(btn => {
            btn.addEventListener('click', prevStep);
        });

        // Message preview functionality
        const messageSelect = document.getElementById('previewMessage');
        const messagePreview = document.getElementById('messagePreviewText');
        if (messageSelect && messagePreview) {
            const messages = {
                1: `Subject: Boost Your Business Efficiency with Our Managed IT Services - Exclusive 10% Off

Dear [Friend's Name],

I hope this email finds you well. I'm reaching out because I've been using an exceptional Managed IT Service that has significantly improved my business efficiency, and I believe it could be a game-changer for you too.

TWE Tech offers top-notch Managed IT Services that have transformed the way I handle my company's technology needs. Here are just a few reasons why I love their service:

• 24/7 Proactive Monitoring: They catch and resolve issues before they impact your business.
• Comprehensive Cybersecurity: State-of-the-art protection against evolving threats.
• Cloud Solutions: Seamless integration of cloud services for enhanced flexibility and scalability.

The best part? By using my referral link, you'll receive an exclusive 10% discount on your first invoice. It's a great way to experience their premium services at a reduced cost.

To take advantage of this offer, simply click on the link below:
[Referral Link]

If you have any questions about my experience with TWE Tech, feel free to reach out. I'd be happy to share more details about how they've improved our IT infrastructure and peace of mind.

Best regards,
[Your Name]`,

                2: `Subject: Revolutionize Your IT Management with TWE Tech - Special 10% Discount

Hello [Friend's Name],

I trust you're doing well. I'm reaching out today because I've discovered a Managed IT Service that I believe could be a game-changer for your business, just as it has been for mine.

TWE Tech provides cutting-edge Managed IT solutions that have revolutionized the way I handle my company's technology needs. Their service stands out due to:

1. Tailored IT Strategies: They develop custom IT plans aligned with your business goals.
2. Rapid Response Times: Their average response time is under 15 minutes, minimizing downtime.
3. Cost-Effective Solutions: By preventing issues and optimizing systems, they've actually reduced our overall IT spend.

As a satisfied customer, I'm excited to share that you can try their service with a special 10% discount on your first invoice. It's an excellent opportunity to experience their premium offerings at a reduced price.

To claim your discount and explore what TWE Tech can do for you, just click this link:
[Referral Link]

I'm confident you'll find their services as valuable as I have. If you'd like to discuss my experience further, please don't hesitate to get in touch.

Warm regards,
[Your Name]`,

                3: `Subject: Elevate Your Business with TWE Tech's Managed IT Services - Exclusive 10% Savings

Dear [Friend's Name],

I hope this message finds you in good spirits. I'm reaching out because I've been using a Managed IT Service that I believe could be incredibly valuable for your business, and I'm excited to share it.

TWE Tech offers exceptional Managed IT Services that have truly streamlined our technology operations and boosted our productivity. Here's why I think you'll love it:

• Predictable IT Budgeting: Their flat-rate pricing model eliminated surprise IT expenses for us.
• Vendor Management: They handle all our tech vendors, saving us time and headaches.
• Business Continuity Planning: Their robust backup and disaster recovery solutions give us peace of mind.

As a way of introducing you to their fantastic service, I can offer you a special 10% discount on your first invoice through my referral link. It's a perfect opportunity to experience their premium features at a reduced cost.

Ready to take your IT management to the next level? Click the link below to get started with your exclusive discount:
[Referral Link]

If you have any questions about my experience with TWE Tech or how their Managed IT Services might benefit your business, please feel free to reach out. I'd be more than happy to discuss it further.

Best wishes,
[Your Name]`
            };

            function updatePreview() {
                const selectedMessage = messages[messageSelect.value];
                const firstName = document.getElementById('firstName').value || '[Friend\'s Name]';
                const senderName = document.getElementById('sender_name').value || '[Your Name]';
                
                let updatedMessage = selectedMessage
                    .replace(/\[Friend's Name\]/g, firstName)
                    .replace(/\[Your Name\]/g, senderName);
                
                messagePreview.innerHTML = updatedMessage.replace(/\n/g, '<br>');
            }

            messageSelect.addEventListener('change', updatePreview);
            
            // Add event listeners to form inputs
            document.getElementById('firstName').addEventListener('input', updatePreview);
            document.getElementById('lastName').addEventListener('input', updatePreview);
            document.getElementById('emailAddress').addEventListener('input', updatePreview);

            // Initial preview update
            updatePreview();

            // Handle form submission
            const submitBtn = wizardNumbered.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', () => {
                    const selectedMessageId = messageSelect.value;
                    const selectedMessage = messages[selectedMessageId];
                    
                    $.ajax({
                        url: 'submit_referral.php',
                        method: 'POST',
                        data: {
                            firstName: document.getElementById('firstName').value,
                            lastName: document.getElementById('lastName').value,
                            emailAddress: document.getElementById('emailAddress').value,
                            message: selectedMessage,
                            sender_name: document.getElementById('sender_name').value
                        },
                        success: function(response) {
                            alert(response.message);
                            console.log(response);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            alert('Error: ' + error);
                        }
                    });
                });
            }
        } else {
            console.error('Wizard element not found');
        }
    }
});
</script>

<?php
require_once "/var/www/nestogy/portal/portal_footer.php";
?>

<link rel="stylesheet" href="/includes/assets/vendor/libs/bs-stepper/bs-stepper.css" />
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'><script src="/includes/assets/vendor/libs/bs-stepper/bs-stepper.js"></script>