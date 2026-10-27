<x-filament-widgets::widget>
    <div class="osds-dashboard">
        <header class="osds-dashboard-intro">
            <h2>Your workspace</h2>
            <p>Open the work available to your account. Case actions depend on your current assignments and the application's stage.</p>
        </header>
        @forelse($groups as $heading => $tasks)
            <section class="osds-dashboard-section" aria-labelledby="workspace-group-{{ $loop->index }}">
                <h3 id="workspace-group-{{ $loop->index }}">{{ $heading }}</h3>
                <div @class(['osds-dashboard-grid', 'osds-dashboard-administration' => $heading === 'Administration'])>
                    @foreach($tasks as $task)
                        <article class="osds-task-card">
                            <h4><a href="{{ $task['url'] }}"><x-filament::icon :icon="$task['icon']" aria-hidden="true" /><span>{{ $task['label'] }}</span></a></h4>
                            <p>{{ $task['description'] }}</p>
                            @if($task['responsibilities'])
                                <ul class="osds-task-responsibilities">
                                    @foreach($task['responsibilities'] as $responsibility)<li>{{ $responsibility }}</li>@endforeach
                                </ul>
                                <p class="osds-task-boundary">Different staff identities must perform the three stages for each case, including earlier revisions.</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="osds-dashboard-empty" aria-labelledby="workspace-empty-heading">
                <h3 id="workspace-empty-heading">No dashboard shortcuts available</h3>
                <p>No dashboard shortcuts are available for your current access. Use an available sidebar item or contact the administrator who manages your account.</p>
            </section>
        @endforelse
    </div>
</x-filament-widgets::widget>
