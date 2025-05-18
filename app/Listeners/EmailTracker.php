<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use App\Models\EmailCampaign;
use App\Models\Lead;

class EmailTracker
{
    public function handle(MessageSent $event)
    {
        $message = $event->message;
        $campaignId = $message->getHeaders()->get('X-Campaign-ID');
        $leadId = $message->getHeaders()->get('X-Lead-ID');

        if ($campaignId && $leadId) {
            $campaign = EmailCampaign::find($campaignId);
            $lead = Lead::find($leadId);

            if ($campaign && $lead) {
                // Record that the email was sent
                $campaign->emailStats()->create([
                    'lead_id' => $lead->id,
                    'sent_at' => now(),
                ]);
            }
        }
    }
}