document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('media-modal');
    const modalCloseBtn = document.querySelector('.modal-close');
    const modalPlayBtn = document.getElementById('modal-play-btn');
    
    let currentMediaId = null;

    // --- Modal Functions ---
    window.openModal = function(item) {
        currentMediaId = item.id;
        document.getElementById('modal-title').innerText = item.title || 'Untitled';
        document.getElementById('modal-overview').innerText = item.overview || 'No overview available.';
        
        const backdropPath = item.backdrop_path;
        const backdropUrl = backdropPath ? `https://image.tmdb.org/t/p/w1280${backdropPath}` : 'assets/images/no-poster.png';
        document.getElementById('modal-backdrop').src = backdropUrl;
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scroll
    };

    window.closeModal = function() {
        closePlayer(); // Stop video if playing
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scroll
        currentMediaId = null;
    };

    // --- Player Functions ---
    function openPlayer() {
        if (!currentMediaId) return;
        
        // Hide details, show video
        document.querySelector('.modal-details').style.display = 'none';
        document.getElementById('modal-backdrop').style.display = 'none';
        const modalVideo = document.getElementById('modal-video');
        modalVideo.style.display = 'block';

        const videoSrc = `stream.php?id=${currentMediaId}`;
        modalVideo.src = videoSrc;
        modalVideo.load(); // Start loading the video

        // Set a timeout for loading
        const loadTimeout = setTimeout(() => {
            if (modalVideo.readyState < 3) { // Have not reached HAVE_FUTURE_DATA
                alert("The video is taking too long to load. This might be due to server speed or an unsupported format.");
                closePlayer();
            }
        }, 15000);

        modalVideo.oncanplay = function() {
            clearTimeout(loadTimeout);
            modalVideo.play().catch(error => {
                console.error("Error attempting to play video:", error);
            });
        };

        modalVideo.onerror = function() {
            clearTimeout(loadTimeout);
            const error = modalVideo.error;
            let message = "Error loading video.";
            if (error) {
                switch (error.code) {
                    case 1: message = "Video loading aborted."; break;
                    case 2: message = "Network error while loading video."; break;
                    case 3: message = "Video format not supported by your browser."; break;
                    case 4: message = "Video could not be loaded (Server error)."; break;
                }
            }
            alert(message);
            closePlayer();
        };
    }

    function closePlayer() {
        const modalVideo = document.getElementById('modal-video');
        modalVideo.pause();
        modalVideo.src = ""; // Clear source
        modalVideo.load();
        modalVideo.style.display = 'none';
        
        // Show details again
        document.querySelector('.modal-details').style.display = 'block';
        document.getElementById('modal-backdrop').style.display = 'block';
    }

    // --- Event Listeners ---
    if (modalCloseBtn) {
        modalCloseBtn.onclick = closeModal;
    }

    if (modalPlayBtn) {
        modalPlayBtn.onclick = openPlayer;
    }

    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    };

    // Close modal with ESC key
    document.onkeydown = function(event) {
        if (event.key === "Escape") {
            if (modal.style.display === 'block') {
                closeModal();
            }
        }
    };
});
