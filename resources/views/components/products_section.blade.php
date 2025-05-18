<div>
    <section class="products-section">
        <h2>Our Products</h2>
        <p>Discover our range of genealogy tools designed to help you explore your ancestry.</p>
        <div class="products-grid">
            @foreach($products as $product)
                <div class="product-card">
                    <img src="{{ $product->image }}" alt="{{ $product->name }}">
                    <div class="product-info">
                        <h3>{{ $product->name }}</h3>
                        <p>{{ $product->description }}</p>
                        <button wire:click="addToCart('{{ $product->id }}')">Add to Cart</button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
