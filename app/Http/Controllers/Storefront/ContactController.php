<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ContactController extends Controller
{
    /**
     * Display Contact Us Page.
     */
    public function index(): View
    {
        return view('storefront.contact', [
            'appName' => setting('name', 'ALLZONE'),
            'companyName' => setting('company_name', 'SK Refrigeration'),
            'supportEmail' => setting('support_email', 'support@allzone.in'),
            'supportPhone' => setting('support_phone', '1800-ALLZONE'),
            'officeAddress' => setting('office_address', 'Corporate Hub, India'),
            'businessHours' => setting('business_hours', 'Monday - Sunday: 8:00 AM - 11:00 PM IST'),
        ]);
    }

    /**
     * Handle Contact Us inquiry form submission.
     */
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
            'subject' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:order,refund,product,account,logistics,general'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $user = Auth::user();
        if (!$user) {
            // Check if user exists by email, otherwise link to first admin or customer system user
            $user = User::where('email', $validated['email'])->first() ?? User::first();
        }

        if ($user) {
            $ticketNumber = 'AZ-TCK-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => TicketPriority::MEDIUM,
                'status' => TicketStatus::OPEN,
            ]);

            TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_type' => get_class($user),
                'sender_id' => $user->id,
                'message' => "Contact Inquiry from {$validated['name']} ({$validated['email']}" . (!empty($validated['phone']) ? ", Phone: +91 " . $validated['phone'] : "") . "):\n\n" . $validated['message'],
            ]);

            return redirect()->route('contact')
                ->with('success', "Thank you, {$validated['name']}! Your message has been received. Ticket #{$ticketNumber} has been created and our team will get back to you shortly.");
        }

        return redirect()->route('contact')
            ->with('success', "Thank you, {$validated['name']}! Your inquiry has been sent to ALLZONE Customer Care.");
    }
}
