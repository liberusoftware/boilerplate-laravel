<div>
    <section class="why-us-section">
        <h2>{{ $title }}</h2>
        <p>{{ $description }}</p>
        <div class="features">
            @foreach($features as $feature)
                <div class="feature-card">
                    <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] }}">
                    <div class="feature-content">
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
