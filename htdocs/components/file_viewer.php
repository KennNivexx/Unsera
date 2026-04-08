<!-- File Viewer logic updated to open in new tab -->
<div id="fileViewerModal" class="hidden"></div>

<script>
function openFileViewer(fileUrl, fileName) {
    if (fileUrl) {
        window.open(fileUrl, '_blank');
    } else {
        alert('File tidak tersedia.');
    }
}

function closeFileViewer() {
    const modal = document.getElementById('fileViewerModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('viewerBody').innerHTML = '';
}

// Close on background click
document.getElementById('fileViewerModal').addEventListener('click', function(e) {
    if (e.target === this) closeFileViewer();
});
</script>
