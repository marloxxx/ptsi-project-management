import './bootstrap';

// Add indicator class to sidebar items with children
function markItemsWithChildren() {
    document.querySelectorAll('.fi-sidebar-item').forEach(item => {
        // Check if item has sub-group-items (rendered when active)
        const subGroupItems = item.querySelector('.fi-sidebar-sub-group-items');
        if (subGroupItems) {
            item.classList.add('fi-sidebar-item-has-children');
        }
    });
}

// Run on initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markItemsWithChildren);
} else {
    markItemsWithChildren();
}

// Run after Livewire updates (Livewire 3)
if (window.Livewire) {
    document.addEventListener('livewire:navigated', markItemsWithChildren);
    document.addEventListener('livewire:init', markItemsWithChildren);
}

// Also run on Alpine initialization
document.addEventListener('alpine:init', () => {
    setTimeout(markItemsWithChildren, 100);
});

// Watch for DOM changes (MutationObserver as fallback)
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(() => {
        markItemsWithChildren();
    });

    // Observe sidebar changes
    const sidebar = document.querySelector('.fi-sidebar-nav');
    if (sidebar) {
        observer.observe(sidebar, {
            childList: true,
            subtree: true
        });
    }
}
