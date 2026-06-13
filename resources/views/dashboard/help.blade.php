@extends('layouts.dashboard')

@section('title', 'Help & Support')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-headset"></i> Help & Support</h2>
        <p>Find answers to common questions and get support.</p>

        <div class="mt-4">
            <div class="accordion" id="helpAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            You can reset your password by going to the login page and clicking "Forgot Password". Follow the instructions sent to your email.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                            How do I upload files?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            Go to the "My Files" section in the left menu and click the upload button. Select your file and it will be uploaded to your account.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                            How do I contact support?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            You can email us at support@example.com or call our support team at 1-800-SUPPORT.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                            Is my data safe?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                        <div class="accordion-body">
                            Yes, we use industry-standard encryption to protect your data. All your information is stored securely on our servers.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-primary mt-4" role="alert">
            <i class="fas fa-envelope"></i> Need more help? Send us an email at <strong>support@example.com</strong>
        </div>
    </div>
@endsection
