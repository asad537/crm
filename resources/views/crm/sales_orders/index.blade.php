@extends('crm.layout')

@section('title', 'Sales Orders')

@section('content')
<div class="content-card" id="sales-orders-card" style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    @include('crm.sales_orders.table')
</div>
@endsection

@section('scripts')
<script>
    function updateSalesOrders(url) {
        const card = document.getElementById('sales-orders-card');
        if (card) {
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
        }

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            if (card) {
                card.innerHTML = html;
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
            window.history.pushState({}, '', url);
        })
        .catch(err => {
            console.error('Filter failed:', err);
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        });
    }

    // Handle AJAX Pagination clicks
    document.addEventListener('click', function(e) {
        let link = e.target.closest('.pagination-wrapper a');
        if (link) {
            e.preventDefault();
            updateSalesOrders(link.href);
        }
    });

    // Auto-refresh data without page reload
    function autoRefreshSalesOrders() {
        // Prevent refresh if a modal is open so we don't disrupt user actions
        const anyModalOpen = Array.from(document.querySelectorAll('[id^="artworkModal"], [id^="rejectProofModal"]'))
            .some(modal => modal.style.display === 'flex');
        
        if (anyModalOpen) {
            return;
        }

        const url = window.location.href;
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            // Check again in case a modal was opened during the request
            const stillNoModalOpen = !Array.from(document.querySelectorAll('[id^="artworkModal"], [id^="rejectProofModal"]'))
                .some(modal => modal.style.display === 'flex');
            
            if (stillNoModalOpen) {
                const card = document.getElementById('sales-orders-card');
                if (card) {
                    card.innerHTML = html;
                }
            }
        })
        .catch(err => {
            console.error('Auto-refresh failed:', err);
        });
    }

    // Poll the server every 10 seconds
    setInterval(autoRefreshSalesOrders, 10000);

    // Handle Artwork Upload with Progress
    function uploadArtworkWithProgress(event, form, orderId) {
        event.preventDefault();
        const formData = new FormData(form);
        const progressContainer = document.getElementById('progress-container-' + orderId);
        const progressBar = document.getElementById('progress-bar-' + orderId);
        const progressText = document.getElementById('progress-text-' + orderId);
        const submitBtn = document.getElementById('submitBtn' + orderId);

        progressContainer.style.display = 'block';
        submitBtn.disabled = true;
        submitBtn.innerText = 'Uploading...';
        submitBtn.style.opacity = '0.7';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressText.innerText = Math.round(percentComplete) + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                submitBtn.innerText = 'Uploaded Successfully!';
                progressBar.style.backgroundColor = '#10b981'; // Green
                setTimeout(() => {
                    // Let auto-refresh handle the UI update, or reload directly
                    window.location.reload();
                }, 800);
            } else {
                alert('Upload failed. Please check the file format or try again.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'Send to Design Department';
                submitBtn.style.opacity = '1';
                progressContainer.style.display = 'none';
            }
        };

        xhr.onerror = function() {
            alert('Network error during upload.');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Send to Design Department';
            submitBtn.style.opacity = '1';
            progressContainer.style.display = 'none';
        };

        xhr.send(formData);
    }
</script>
@endsection
