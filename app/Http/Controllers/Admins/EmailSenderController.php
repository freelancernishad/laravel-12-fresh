<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Mail\DynamicEmail;
use App\Jobs\SendBulkEmail;
use Illuminate\Support\Facades\Mail;

class EmailSenderController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::latest()->get();
        $users = User::all();
        return view('admin.email-sender.index', compact('templates', 'users'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'recipients' => 'nullable|array',
            'manual_emails' => 'nullable|string',
        ]);

        $template = EmailTemplate::find($request->template_id);
        $recipientsData = [];

        // Add manual emails
        if ($request->manual_emails) {
            $emails = array_map('trim', explode(',', $request->manual_emails));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipientsData[] = ['email' => $email, 'name' => 'User'];
                }
            }
        }

        // Add selected users
        if ($request->recipients) {
            $users = [];
            if (in_array('all', $request->recipients)) {
                $users = User::all(['email', 'name']);
            } else {
                $users = User::whereIn('id', $request->recipients)->get(['email', 'name']);
            }

            foreach ($users as $user) {
                $recipientsData[] = ['email' => $user->email, 'name' => $user->name];
            }
        }

        // Deduplicate by email
        $uniqueRecipients = [];
        $emailsSeen = [];
        foreach ($recipientsData as $data) {
            if (!in_array($data['email'], $emailsSeen)) {
                $uniqueRecipients[] = $data;
                $emailsSeen[] = $data['email'];
            }
        }

        if (empty($uniqueRecipients)) {
            return back()->with('error', 'No valid recipients selected.');
        }

        if (count($uniqueRecipients) > 1) {
            // Bulk sending via Queue
            SendBulkEmail::dispatch($uniqueRecipients, $template->subject, $template->content_html, $template->id);
            return back()->with('success', 'Bulk emails have been queued for sending.');
        } else {
            // Single sending
            $recipient = $uniqueRecipients[0];
            Mail::to($recipient['email'])->send(new DynamicEmail($template->subject, $template->content_html, ['name' => $recipient['name']]));
            
            // Log the single email
            \App\Models\EmailLog::create([
                'recipient' => $recipient['email'],
                'subject' => $template->subject,
                'status' => 'sent',
                'template_id' => $template->id,
            ]);

            return back()->with('success', 'Email sent successfully.');
        }
    }

    public function sendTest(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'subject' => 'required|string',
            'content_html' => 'required|string',
        ]);

        Mail::to($request->email)->send(new DynamicEmail($request->subject, $request->content_html, ['name' => 'Test User']));

        return response()->json(['success' => true, 'message' => 'Test email sent successfully to ' . $request->email]);
    }
}
