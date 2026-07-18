<?php

namespace App\Livewire\Eventner\Faq;

use App\Models\EventFaq;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.admin')]
class Index extends Component
{
    public $question = '';
    public $answer = '';
    public $editingId = null;

    public function save()
    {
        $this->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:2000',
        ]);

        if ($this->editingId) {
            $faq = EventFaq::where('eventner_id', Auth::user()->eventner->id)->findOrFail($this->editingId);
            $faq->update(['question' => strip_tags($this->question), 'answer' => strip_tags($this->answer)]);
        } else {
            EventFaq::create([
                'eventner_id' => Auth::user()->eventner->id,
                'question' => strip_tags($this->question),
                'answer' => strip_tags($this->answer),
                'sort_order' => EventFaq::where('eventner_id', Auth::user()->eventner->id)->max('sort_order') + 1,
            ]);
        }

        $this->reset(['question', 'answer', 'editingId']);
        session()->flash('success', 'FAQ berhasil disimpan.');
    }

    public function edit($id)
    {
        $faq = EventFaq::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id);
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
    }

    public function delete($id)
    {
        EventFaq::where('eventner_id', Auth::user()->eventner->id)->findOrFail($id)->delete();
        session()->flash('success', 'FAQ dihapus.');
    }

    public function render()
    {
        return view('livewire.eventner.faq.index', [
            'faqs' => EventFaq::where('eventner_id', Auth::user()->eventner->id)->orderBy('sort_order')->get(),
        ])->title('FAQ - BARIS APP');
    }
}
