<div class="container mx-auto mt-2">
    <h2 class="text-2xl font-bold mb-4">Manage Your Genealogy</h2>
    <p class="mb-8">Explore and manage your family tree with ease.</p>
    <div class="manage-section grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card bg-white shadow-md hover:shadow-lg rounded-lg cursor-pointer transition transform hover:-translate-y-1" wire:click="navigateToFamilyTree">
            <div class="card-body p-4">
                <h5 class="text-lg font-bold mb-2">Family Tree</h5>
                <p class="text-gray-700">View and edit your family tree.</p>
            </div>
        </div>
        <div class="card bg-white shadow-md hover:shadow-lg rounded-lg cursor-pointer transition transform hover:-translate-y-1" wire:click="navigateToRecords">
            <div class="card-body p-4">
                <h5 class="text-lg font-bold mb-2">Records</h5>
                <p class="text-gray-700">Access and manage historical records.</p>
            </div>
        </div>
        <div class="card bg-white shadow-md hover:shadow-lg rounded-lg cursor-pointer transition transform hover:-translate-y-1" wire:click="navigateToSettings">
            <div class="card-body p-4">
                <h5 class="text-lg font-bold mb-2">Settings</h5>
                <p class="text-gray-700">Configure your account and preferences.</p>
            </div>
        </div>
    </div>
</div>
