<div>
    <form wire:submit.prevent="generateReport">
        <div class="flex items-end">
            <div class="flex-1 pr-2">{{ $this->form }}</div>
            <div>{{ $this->generateAction }}</div>
        </div>
    </form>

    <div class="flex justify-center text-center mt-3" wire:loading>
        Generating report...
    </div>

    @if (!empty($reportData))
    <div v-for="data in reportLevels">
        <div :style="`margin: 8px 6px 8px ${data.level * 42}px; display: flex`">
          <span :style="`width: ${(data.level * 5) + 20}px;`">{{data.label}}</span> 
          <span>{{ data.person.firstNames }}</span>
        </div>
        <div v-if="data.person.spouse && data.person.children" :style="`margin: 8px 0px 16px ${(data.level * 42)}px; display: flex`">
          <span :style="`padding: 0 8px 0px ${(data.level * 5) + 20}px`">sp</span> 
          <span>{{ data.person.spouse.firstNames }}</span>
        </div>
      </div>
    @endif
</div>
