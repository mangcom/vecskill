document.addEventListener('DOMContentLoaded', function() {
    // --- Elements ---
    const contentDisplay = document.getElementById('content-display');
    const menuItems = document.querySelectorAll('.menu li');
    const mainMenu = document.getElementById('main-menu');
    // Responsive Menu Elements
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    // Sidebar Collapse Elements (New)
    const collapseSidebarBtn = document.getElementById('collapse-sidebar-btn');
    const body = document.body; // Reference to body

    // --- Function to add Copy Buttons ---
    function addCopyButtons() {
        const preBlocks = contentDisplay.querySelectorAll('pre:not(.copy-button-added)');
        preBlocks.forEach(pre => {
            const container = document.createElement('div');
            container.className = 'code-container';
            pre.parentNode.insertBefore(container, pre);
            container.appendChild(pre);

            const copyButton = document.createElement('button');
            copyButton.className = 'copy-btn';
            // Use Font Awesome icon for Copy
            copyButton.innerHTML = '<i class="far fa-copy"></i>'; // Use 'far' for regular style
            copyButton.setAttribute('title', 'Copy code'); // Add tooltip

            container.appendChild(copyButton);

            copyButton.addEventListener('click', () => {
                const codeElement = pre.querySelector('code');
                if (!codeElement) return;
                const codeToCopy = codeElement.innerText;

                navigator.clipboard.writeText(codeToCopy).then(() => {
                    // Use Font Awesome icon for Check
                    copyButton.innerHTML = '<i class="fas fa-check"></i>'; // Use 'fas' for solid style
                    copyButton.classList.add('copied');
                    copyButton.setAttribute('title', 'Copied!');

                    setTimeout(() => {
                        copyButton.innerHTML = '<i class="far fa-copy"></i>';
                        copyButton.classList.remove('copied');
                        copyButton.setAttribute('title', 'Copy code');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy code: ', err);
                    copyButton.innerHTML = '<i class="fas fa-times"></i>'; // Error icon
                    copyButton.setAttribute('title', 'Copy failed!');
                });
            });
            pre.classList.add('copy-button-added');
        });
    }

    // --- Function to load content ---
    function loadContent(page) {
        contentDisplay.innerHTML = '<h1><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</h1>';
        fetch(`pages/${page}.html`)
            .then(response => {
                if (!response.ok) { throw new Error(`ไม่พบไฟล์: ${page}.html`); }
                return response.text();
            })
            .then(data => {
                contentDisplay.innerHTML = data;
                addCopyButtons(); // Add copy buttons after content loads
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
                contentDisplay.innerHTML = `<h1>เกิดข้อผิดพลาด</h1><p>${error.message}</p>`;
            });
    }

    // --- Function to toggle mobile sidebar visibility ---
    function toggleMobileSidebar() {
        body.classList.toggle('sidebar-visible');
    }

    // --- Function to toggle desktop sidebar collapse state --- (New)
    function toggleDesktopCollapse() {
        body.classList.toggle('sidebar-collapsed');
        // Optional: Store preference in localStorage
        // localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
    }

    // --- Event Listeners ---
    // Mobile menu toggle
    if (menuToggle) { menuToggle.addEventListener('click', toggleMobileSidebar); }
    if (closeMenuBtn) { closeMenuBtn.addEventListener('click', toggleMobileSidebar); }

    // Desktop sidebar collapse toggle (New)
    if (collapseSidebarBtn) { collapseSidebarBtn.addEventListener('click', toggleDesktopCollapse); }

    // Menu link clicks
    mainMenu.addEventListener('click', function(event) {
        const link = event.target.closest('a'); // Find the clicked link
        if (link && link.hasAttribute('data-page')) {
            event.preventDefault();
            menuItems.forEach(item => item.classList.remove('active'));
            link.closest('li').classList.add('active'); // Add active to the parent LI
            const page = link.getAttribute('data-page');
            loadContent(page);
            // Close mobile sidebar if open after clicking a link
            if (window.innerWidth <= 800 && body.classList.contains('sidebar-visible')) {
                toggleMobileSidebar();
            }
        }
    });

    // --- Initial Load ---
    // Optional: Check localStorage for collapsed state
    // if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 800) {
    //     body.classList.add('sidebar-collapsed');
    // }
    loadContent('project-overview'); // Load initial page

    // Tab handler (remains the same)
    contentDisplay.addEventListener('click', function(event) {
        if (event.target.classList.contains('tablinks')) {
            const tabName = event.target.getAttribute('data-target-tab');
            const tabContainer = event.target.closest('.tab').parentElement;
            const tabcontent = tabContainer.querySelectorAll(".tabcontent");
            const tablinks = tabContainer.querySelectorAll(".tablinks");
            tabcontent.forEach(tab => tab.style.display = "none");
            tablinks.forEach(link => link.classList.remove("active"));
            tabContainer.querySelector(`#${tabName}`).style.display = "block";
            event.target.classList.add("active");
        }
    });
});