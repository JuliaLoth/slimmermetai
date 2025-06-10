<?php

namespace Tests\Integration;

use App\Application\Service\EmailService;
use App\Infrastructure\Mail\Mailer;

/**
 * EmailService Integration Tests
 * 
 * Test echte email operaties zonder daadwerkelijk emails te versturen
 */
class EmailServiceIntegrationTest extends BaseIntegrationTest
{
    private EmailService $emailService;
    private TestMailer $testMailer;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Test mailer die emails vastlegt in plaats van te versturen
        $this->testMailer = new TestMailer();
        
        // Echte EmailService met test mailer
        $this->emailService = new EmailService($this->testMailer);
    }
    
    /**
     * Test password reset email verzending
     */
    public function testPasswordResetEmailSending(): void
    {
        // ARRANGE: Test data
        $email = $this->getTestFixture('user_email');
        $resetToken = 'test-reset-token-' . uniqid();
        $userName = 'Test User';
        
        // ACT: Verstuur password reset email
        $result = $this->emailService->sendPasswordResetEmail($email, $resetToken, $userName);
        
        // ASSERT: Email service retourneert success
        $this->assertTrue($result, 'Password reset email versturen moet succesvol zijn');
        
        // ASSERT: Test mailer heeft email ontvangen
        $this->assertCount(1, $this->testMailer->getSentEmails(), 'Er moet 1 email verstuurd zijn');
        
        $sentEmail = $this->testMailer->getSentEmails()[0];
        
        // ASSERT: Email heeft correcte gegevens
        $this->assertEquals($email, $sentEmail['to']);
        $this->assertStringContainsString('Wachtwoord reset', $sentEmail['subject']);
        $this->assertStringContainsString($resetToken, $sentEmail['body']);
        $this->assertStringContainsString($userName, $sentEmail['body']);
        $this->assertStringContainsString('SlimmerMetAI', $sentEmail['body']);
    }
    
    /**
     * Test welcome email verzending
     */
    public function testWelcomeEmailSending(): void
    {
        // ARRANGE: Test data
        $email = 'newuser@slimmermetai.nl';
        $userName = 'Nieuwe Gebruiker';
        
        // ACT: Verstuur welcome email
        $result = $this->emailService->sendWelcomeEmail($email, $userName);
        
        // ASSERT: Email service retourneert success
        $this->assertTrue($result, 'Welcome email versturen moet succesvol zijn');
        
        // ASSERT: Test mailer heeft email ontvangen
        $this->assertCount(1, $this->testMailer->getSentEmails(), 'Er moet 1 email verstuurd zijn');
        
        $sentEmail = $this->testMailer->getSentEmails()[0];
        
        // ASSERT: Email heeft correcte gegevens
        $this->assertEquals($email, $sentEmail['to']);
        $this->assertStringContainsString('Welkom', $sentEmail['subject']);
        $this->assertStringContainsString($userName, $sentEmail['body']);
        $this->assertStringContainsString('SlimmerMetAI', $sentEmail['body']);
    }
    
    /**
     * Test email template rendering
     */
    public function testEmailTemplateRendering(): void
    {
        // ARRANGE: Template variabelen
        $templateVars = [
            'user_name' => 'John Doe',
            'reset_url' => 'https://slimmermetai.nl/reset?token=abc123',
            'site_name' => 'SlimmerMetAI',
            'support_email' => 'support@slimmermetai.nl'
        ];
        
        // ACT: Render email template
        $renderedHtml = $this->emailService->renderTemplate('password-reset', $templateVars);
        
        // ASSERT: Template is correct gerenderd
        $this->assertIsString($renderedHtml);
        $this->assertStringContainsString($templateVars['user_name'], $renderedHtml);
        $this->assertStringContainsString($templateVars['reset_url'], $renderedHtml);
        $this->assertStringContainsString($templateVars['site_name'], $renderedHtml);
        $this->assertStringContainsString($templateVars['support_email'], $renderedHtml);
        
        // ASSERT: HTML structuur is aanwezig
        $this->assertStringContainsString('<html', $renderedHtml);
        $this->assertStringContainsString('</html>', $renderedHtml);
        $this->assertStringContainsString('<body', $renderedHtml);
        $this->assertStringContainsString('</body>', $renderedHtml);
    }
    
    /**
     * Test bulk email verzending
     */
    public function testBulkEmailSending(): void
    {
        // ARRANGE: Meerdere ontvangers
        $recipients = [
            ['email' => 'user1@test.com', 'name' => 'User 1'],
            ['email' => 'user2@test.com', 'name' => 'User 2'],
            ['email' => 'user3@test.com', 'name' => 'User 3']
        ];
        
        $subject = 'Nieuwsbrief Update';
        $template = 'newsletter';
        
        // ACT: Verstuur bulk emails
        $results = $this->emailService->sendBulkEmails($recipients, $subject, $template);
        
        // ASSERT: Alle emails zijn verstuurd
        $this->assertCount(3, $results, 'Er moeten 3 resultaten zijn');
        $this->assertCount(3, $this->testMailer->getSentEmails(), 'Er moeten 3 emails verstuurd zijn');
        
        // ASSERT: Elk resultaat is success
        foreach ($results as $result) {
            $this->assertTrue($result['success'], 'Elke bulk email moet succesvol zijn');
        }
        
        // ASSERT: Emails hebben correcte ontvangers
        $sentEmails = $this->testMailer->getSentEmails();
        for ($i = 0; $i < 3; $i++) {
            $this->assertEquals($recipients[$i]['email'], $sentEmails[$i]['to']);
            $this->assertEquals($subject, $sentEmails[$i]['subject']);
            $this->assertStringContainsString($recipients[$i]['name'], $sentEmails[$i]['body']);
        }
    }
    
    /**
     * Test email verzending failure handling
     */
    public function testEmailSendingFailureHandling(): void
    {
        // ARRANGE: Configureer test mailer om te falen
        $this->testMailer->setShouldFail(true);
        
        $email = 'test@example.com';
        $resetToken = 'test-token';
        $userName = 'Test User';
        
        // ACT: Probeer email te versturen
        $result = $this->emailService->sendPasswordResetEmail($email, $resetToken, $userName);
        
        // ASSERT: Email service retourneert false bij failure
        $this->assertFalse($result, 'Email versturen moet false returnen bij failure');
        
        // ASSERT: Geen emails verstuurd
        $this->assertCount(0, $this->testMailer->getSentEmails(), 'Er mogen geen emails verstuurd zijn');
    }
    
    /**
     * Test email validatie
     */
    public function testEmailValidation(): void
    {
        // ARRANGE: Test email adressen
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'firstname+lastname@company.org'
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            '',
            null
        ];
        
        // ACT & ASSERT: Geldige emails
        foreach ($validEmails as $email) {
            $result = $this->emailService->validateEmailAddress($email);
            $this->assertTrue($result, "Email '$email' moet geldig zijn");
        }
        
        // ACT & ASSERT: Ongeldige emails
        foreach ($invalidEmails as $email) {
            $result = $this->emailService->validateEmailAddress($email);
            $this->assertFalse($result, "Email '$email' moet ongeldig zijn");
        }
    }
    
    /**
     * Test email queue functionaliteit
     */
    public function testEmailQueueFunctionality(): void
    {
        // ARRANGE: Meerdere emails in queue
        $emails = [
            ['to' => 'user1@test.com', 'subject' => 'Test 1', 'template' => 'test'],
            ['to' => 'user2@test.com', 'subject' => 'Test 2', 'template' => 'test'],
            ['to' => 'user3@test.com', 'subject' => 'Test 3', 'template' => 'test']
        ];
        
        // ACT: Voeg emails toe aan queue
        foreach ($emails as $emailData) {
            $this->emailService->queueEmail(
                $emailData['to'], 
                $emailData['subject'], 
                $emailData['template']
            );
        }
        
        // ASSERT: Queue bevat emails
        $queueSize = $this->emailService->getQueueSize();
        $this->assertEquals(3, $queueSize, 'Queue moet 3 emails bevatten');
        
        // ACT: Process queue
        $processed = $this->emailService->processEmailQueue();
        
        // ASSERT: Alle emails geprocessed
        $this->assertEquals(3, $processed, 'Er moeten 3 emails geprocessed zijn');
        $this->assertEquals(0, $this->emailService->getQueueSize(), 'Queue moet leeg zijn na processing');
        $this->assertCount(3, $this->testMailer->getSentEmails(), 'Er moeten 3 emails verstuurd zijn');
    }
}

/**
 * Test Mailer Implementation
 * 
 * Simuleert email verzending voor tests
 */
class TestMailer implements \App\Infrastructure\Mail\MailerInterface
{
    private array $sentEmails = [];
    private bool $shouldFail = false;
    
    public function send(string $to, string $subject, string $html, ?string $from = null): bool
    {
        if ($this->shouldFail) {
            return false;
        }
        
        $this->sentEmails[] = [
            'to' => $to,
            'subject' => $subject,
            'body' => $html,
            'from' => $from,
            'sent_at' => time()
        ];
        
        return true;
    }
    
    public function getSentEmails(): array
    {
        return $this->sentEmails;
    }
    
    public function setShouldFail(bool $shouldFail): void
    {
        $this->shouldFail = $shouldFail;
    }
    
    public function clearSentEmails(): void
    {
        $this->sentEmails = [];
    }
} 