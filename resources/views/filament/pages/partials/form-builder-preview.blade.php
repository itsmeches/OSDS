@if($saveState!=='Saved')<p class="builder-preview-unsaved">Showing unsaved changes</p>@endif
@foreach($sections as $section)
<section class="builder-preview-section"><h3>{{ $section['title']?:'Untitled section' }}</h3>@if($section['description'])<p>{{ $section['description'] }}</p>@endif<div class="form-stack">
@foreach($section['questions'] as $question)
@if($this->visible($question))
<div id="preview-question-{{ $question['key'] }}" @class(['builder-preview-field','is-selected'=>$selectedQuestionKey===$question['key']])>
<x-questionnaires.field :question="$question" model="previewAnswers.{{ $question['key'] }}" :disabled="!$previewTesting" />
</div>
@endif
@endforeach
</div></section>
@endforeach
<section class="builder-preview-section"><h3>Required documents</h3>@foreach($documentRequirements as $requirement)@if($this->visibleRequirement($requirement))<x-questionnaires.requirement :requirement="$requirement" />@endif @endforeach</section>
<p class="builder-preview-note">Preview answers are temporary and are never saved.</p>
