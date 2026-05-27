    </div> <!-- Close admin-content -->
</div> <!-- Close admin-main -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Global AJAX setup
$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Function to show loading
function showLoading() {
    if($('#loadingOverlay').length === 0) {
        $('body').append('<div id="loadingOverlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:none;align-items:center;justify-content:center"><div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    }
    $('#loadingOverlay').fadeIn().css('display', 'flex');
}

function hideLoading() {
    $('#loadingOverlay').fadeOut();
}

$(document).ajaxStart(showLoading);
$(document).ajaxStop(hideLoading);

// Confirm delete function
function confirmDelete(url, message) {
    Swal.fire({
        title: 'Are you sure?',
        text: message || "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>

</body>
</html>