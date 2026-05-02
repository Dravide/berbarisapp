<?php

namespace App\Livewire\Public;

use App\Models\CompetitionCategory;
use App\Models\Eventner;
use App\Models\Registration;
use App\Models\VoteTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

#[Layout('layouts.frontend')]
class EventVote extends Component
{
    public $eventner;
    public $view = 'categories'; // 'categories' or 'participants'
    public $selectedCategoryId;
    public $search = '';
    public $selectedRegistrationId;
    public $voterName;
    public $voterEmail;
    public $voteCount = 10;
    
    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategoryId' => ['except' => ''],
    ];

    protected $rules = [
        'selectedRegistrationId' => 'required|exists:registrations,id',
        'voterName' => 'required|string|max:255',
        'voterEmail' => 'required|email|max:255',
        'voteCount' => 'required|integer|min:1',
    ];

    public function mount($slug)
    {
        $this->eventner = Eventner::where('slug', $slug)->firstOrFail();
            
        if ($this->selectedCategoryId) {
            $this->view = 'participants';
        }
    }

    public function selectCategory($id)
    {
        $this->selectedCategoryId = $id;
        $this->view = 'participants';
    }

    public function backToCategories()
    {
        $this->view = 'categories';
        $this->selectedCategoryId = null;
        $this->search = '';
    }

    public function selectTeam($id)
    {
        $this->selectedRegistrationId = $id;
    }

    public function submitVote()
    {
        if (RateLimiter::tooManyAttempts('vote-submit:'.request()->ip(), $maxAttempts = 5)) {
            session()->flash('error', 'Terlalu banyak permintaan. Silakan coba lagi dalam satu menit.');
            return;
        }

        RateLimiter::hit('vote-submit:'.request()->ip(), $decaySeconds = 60);

        $this->validate();

        $amount = $this->voteCount * 1000;
        $registration = Registration::find($this->selectedRegistrationId);

        Configuration::setApiKey(config('services.xendit.key'));
        $apiInstance = new InvoiceApi();

        $externalId = 'VOTE-' . time() . '-' . $this->selectedRegistrationId;

        $customer = new \Xendit\Invoice\CustomerObject([
            'given_names' => $this->voterName,
            'email' => $this->voterEmail,
        ]);

        $createInvoiceRequest = new CreateInvoiceRequest([
            'external_id' => $externalId,
            'amount' => (float)$amount,
            'payer_email' => $this->voterEmail,
            'description' => "Voting Digital untuk " . $registration->nama_sekolah . " di event " . $this->eventner->nama_event,
            'customer' => $customer,
            'success_redirect_url' => route('event.detail', $this->eventner->slug),
            'failure_redirect_url' => route('event.vote', $this->eventner->slug),
            'currency' => 'IDR',
            'reminder_time' => 1,
        ]);

        try {
            $invoice = $apiInstance->createInvoice($createInvoiceRequest);

            VoteTransaction::create([
                'eventner_id' => $this->eventner->id,
                'registration_id' => $this->selectedRegistrationId,
                'xendit_invoice_id' => $invoice->getId(),
                'xendit_invoice_url' => $invoice->getInvoiceUrl(),
                'amount' => $amount,
                'votes_earned' => $this->voteCount,
                'voter_name' => $this->voterName,
                'voter_email' => $this->voterEmail,
                'status' => 'PENDING',
            ]);

            return redirect()->away($invoice->getInvoiceUrl());

        } catch (\Exception $e) {
            Log::error('Xendit invoice creation failed', [
                'external_id' => $externalId,
                'registration_id' => $this->selectedRegistrationId,
                'amount' => $amount,
                'voter_email' => $this->voterEmail,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Gagal membuat invoice: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $participants = collect();
        $selectedCategory = null;

        if ($this->selectedCategoryId) {
            $selectedCategory = CompetitionCategory::find($this->selectedCategoryId);
            
            $query = Registration::where('competition_category_id', $this->selectedCategoryId);
            
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('nama_sekolah', 'like', '%' . $this->search . '%')
                      ->orWhere('nama_pelatih', 'like', '%' . $this->search . '%');
                });
            }
            
            $participants = $query->get();
        }

        return view('livewire.public.event-vote', [
            'participants' => $participants,
            'selectedCategory' => $selectedCategory,
            'categories' => $this->eventner->competitionCategories->loadCount('registrations')
        ])->title('Vote Peserta - ' . $this->eventner->nama_event)
         ->layoutData(['eventner' => $this->eventner]);
    }
}
