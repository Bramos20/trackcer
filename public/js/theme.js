window.themeToggleInitialized = true;

// Theme toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, looking for theme toggle button');

    const themeToggleBtn = document.getElementById('theme-toggle');
    console.log('Theme toggle button found:', themeToggleBtn);

    if (!themeToggleBtn) {
        console.error('Theme toggle button not found!');
        return;
    }

    const lightIcon = document.getElementById('theme-toggle-light-icon');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');

    // Function to update icon visibility
    function updateIconVisibility() {
        const isDark = document.documentElement.classList.contains('dark');
        console.log('Current theme is dark:', isDark);

        // Toggle the d-none class based on the current theme
        if (isDark) {
            darkIcon.classList.add('d-none');
            lightIcon.classList.remove('d-none');
        } else {
            darkIcon.classList.remove('d-none');
            lightIcon.classList.add('d-none');
        }
    }

    // Set initial icon state
    updateIconVisibility();

    // Add click event listener
    themeToggleBtn.addEventListener('click', function() {
        console.log('Theme toggle clicked!');

        const isDark = document.documentElement.classList.contains('dark');
        const newTheme = isDark ? 'light' : 'dark';

        console.log('Switching from', isDark ? 'dark' : 'light', 'to', newTheme);

        // Remove current theme class
        document.documentElement.classList.remove(isDark ? 'dark' : 'light');
        // Add new theme class
        document.documentElement.classList.add(newTheme);

        // Save preference to localStorage
        localStorage.setItem('theme', newTheme);
        console.log('Theme saved to localStorage:', newTheme);

        // Update icon visibility
        updateIconVisibility();
    });
});
