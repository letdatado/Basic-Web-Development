document.addEventListener('DOMContentLoaded', function () {
    // Interactive UI features once the DOM is ready
    setupLikeButtons();
    setupDemoForms();
});

function setupLikeButtons() {
    // Like buttons appear on idea.php
    const likeButtons = document.querySelectorAll('.like-button');

    likeButtons.forEach(function (button) {
        button.addEventListener('click', function () {

            // Read idea id 
            const ideaId = this.dataset.ideaId;
            if (!ideaId) return;

            // Locate the element that displays the like count near this button
            const countSpan = this.parentElement.querySelector('.like-count');

            // Track state from dataliked
            const isLiked = this.dataset.liked === 'true';

            // Send form style POST data to like.php
            const formData = new FormData();
            formData.append('idea_id', ideaId);
            formData.append('action', isLiked ? 'unlike' : 'like');

            fetch('like.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {

                // If backend refuses an OK, handle expected cases
                if (!data.ok) {
                    if (data.error === 'not_logged_in') {
                        // redirect guest users to login
                        window.location.href = 'login.php';
                        return;
                    }
                    // Otherwise fail silently 
                    return;
                }

                // Update like count
                if (countSpan) {
                    countSpan.textContent = data.likes;
                }

                // Toggle button state and styling
                if (isLiked) {
                    button.dataset.liked = 'false';
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-outline-primary');
                } else {
                    button.dataset.liked = 'true';
                    button.classList.remove('btn-outline-primary');
                    button.classList.add('btn-primary');
                }
            })
            .catch(function () {
                // Silent failure is OK for now
            });
        });
    });
}

function setupDemoForms() {
    // prevents submission and shows an informational alert
    // ffects forms that opt-in 
    const forms = document.querySelectorAll('.js-demo-form');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // Prevent the form from actually submitting
            event.preventDefault();

            // Remove any existing alert inside this form
            const oldAlert = form.querySelector('.js-demo-alert');
            if (oldAlert) {
                oldAlert.remove();
            }

            // Create a Bootstrap alert element
            const alert = document.createElement('div');
            alert.className = 'alert alert-info mt-3 js-demo-alert';
            alert.textContent = 'Demo only: this will save your idea or comment in the final version.';

            // Append alert to form so the user sees feedback
            form.appendChild(alert);

            // Clear text fields to indicate a successful submission
            const inputs = form.querySelectorAll('input[type="text"], textarea');
            inputs.forEach(function (input) {
                input.value = '';
            });

            // Remove the alert after 4 seconds
            setTimeout(function () {
                if (alert && alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 4000);
        });
    });
}
