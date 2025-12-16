document.addEventListener('DOMContentLoaded', function() {
    // --- (ส่วนเลือก Elements และปุ่ม Copy/Sidebar/Menu... เหมือนเดิม) ---
    const menuLinks = document.querySelectorAll('.menu a');
    const contentDisplay = document.getElementById('content-display');
    const menuItems = document.querySelectorAll('.menu li');
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mainMenu = document.getElementById('main-menu');
    const collapseSidebarBtn = document.getElementById('collapse-sidebar-btn');
    const body = document.body;

    // --- (ฟังก์ชัน addCopyButtons เหมือนเดิม) ---
    function addCopyButtons() {
        // ... (โค้ดปุ่ม Copy ทั้งหมด) ...
        const preBlocks = contentDisplay.querySelectorAll('pre:not(.copy-button-added)');
        preBlocks.forEach(pre => {
            const container = document.createElement('div');
            container.className = 'code-container';
            pre.parentNode.insertBefore(container, pre);
            container.appendChild(pre);
            const copyButton = document.createElement('button');
            copyButton.className = 'copy-btn';
            copyButton.innerHTML = '<i class="far fa-copy"></i>';
            copyButton.setAttribute('title', 'Copy code');
            container.appendChild(copyButton);
            copyButton.addEventListener('click', () => {
                const codeElement = pre.querySelector('code');
                if (!codeElement) return;
                const codeToCopy = codeElement.innerText;
                navigator.clipboard.writeText(codeToCopy).then(() => {
                    copyButton.innerHTML = '<i class="fas fa-check"></i>';
                    copyButton.classList.add('copied');
                    copyButton.setAttribute('title', 'Copied!');
                    setTimeout(() => {
                        copyButton.innerHTML = '<i class="far fa-copy"></i>';
                        copyButton.classList.remove('copied');
                        copyButton.setAttribute('title', 'Copy code');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy code: ', err);
                    copyButton.innerHTML = '<i class="fas fa-times"></i>';
                    copyButton.setAttribute('title', 'Copy failed!');
                });
            });
            pre.classList.add('copy-button-added');
        });
    }

    // --- (ฟังก์ชัน populateStatistics อัปเดตสำหรับ DataTables) ---
    async function populateStatistics() {
        const statsPlaceholder = document.getElementById('stats-placeholder');
        
        // 1. ตรวจสอบว่า DataTables โหลดมาหรือยัง
        if (typeof jQuery === 'undefined' || !jQuery.fn.DataTable) {
            if (statsPlaceholder) statsPlaceholder.innerHTML = "<p style='color: red;'>ข้อผิดพลาด: ไม่สามารถโหลด Library ของ DataTables ได้</p>";
            console.error("DataTables library not found!");
            return;
        }

        const statsTable = document.getElementById('stats-table');
        if (!statsTable) return;
        
        // 2. (สำคัญ) ทำลายตารางเก่าทิ้งถ้ามี (กรณีคลิกซ้ำ)
        if (jQuery.fn.DataTable.isDataTable('#stats-table')) {
            jQuery('#stats-table').DataTable().destroy();
        }

        // 3. แสดงสถานะกำลังโหลดใน tbody
        const tableBody = statsTable.querySelector('tbody');
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="3">กำลังโหลดสถิติจาก Server...</td></tr>';

        try {
            // 4. ยิง API ไปยัง Backend
            const response = await fetch('/api/stats.php'); 
            if (!response.ok) {
                throw new Error(`Server error: ${response.statusText}`);
            }
            const allCounts = await response.json();

            // 5. กรองข้อมูล
            const filteredCounts = allCounts.filter(item => 
                item.page_name !== 'project-overview' && 
                item.page_name !== 'statistics'
            );

            // 6. (ใหม่) เตรียมข้อมูลสำหรับ DataTables
            const dataForTable = filteredCounts.map((item, index) => {
                const menuLink = document.querySelector(`.menu a[data-page="${item.page_name}"] span`);
                const displayName = menuLink ? menuLink.textContent.trim() : item.page_name;
                return {
                    rank: index + 1,
                    name: `${displayName} (<code>${item.page_name}</code>)`,
                    views: item.views
                };
            });

            // 7. (ใหม่) เริ่มใช้งาน DataTables
            jQuery('#stats-table').DataTable({
                data: dataForTable,
                columns: [
                    { data: 'rank', title: 'อันดับ' },
                    { data: 'name', title: 'ชื่อหน้า (Page ID)' },
                    { data: 'views', title: 'จำนวนครั้งที่เข้าชม' }
                ],
                order: [[2, 'desc']], // เรียงลำดับตามยอดวิว (คอลัมน์ 2) จากมากไปน้อย
                paging: true,        // เปิดการแบ่งหน้า
                searching: true,     // เปิดการค้นหา
                info: true,
                responsive: true,    // รองรับการแสดงผลบนมือถือ
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" // ใช้ภาษาไทย
                }
            });

        } catch (error) {
            console.error('Failed to fetch stats:', error);
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="3" style="color: red;">ไม่สามารถโหลดสถิติได้</td></tr>';
        }
    }

    // --- (ฟังก์ชัน loadContent อัปเดตใหม่) ---
    function loadContent(page) {
        contentDisplay.innerHTML = '<h1><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</h1>';

        // --- View Counting Logic (Server-side) ---
        if (page !== 'statistics') {
            fetch('/api/track.php', { 
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ page: page })
            }).catch(err => {
                console.warn('Failed to track page view:', err.message);
            });
        }
        // --- End View Counting Logic ---

        fetch(`pages/${page}.html`)
            .then(response => {
                if (!response.ok) { throw new Error(`ไม่พบไฟล์: ${page.html}`); }
                return response.text();
            })
            .then(data => {
                contentDisplay.innerHTML = data;
                addCopyButtons(); // เรียกใช้ฟังก์ชันปุ่ม Copy

                if (page === 'statistics') {
                    populateStatistics();
                }
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
                contentDisplay.innerHTML = `<h1>เกิดข้อผิดพลาด</h1><p>${error.message}</p>`;
            });
    }

    // --- (ส่วนที่เหลือของ script.js ... toggle functions, event listeners ... เหมือนเดิม) ---
    function toggleMobileSidebar() { body.classList.toggle('sidebar-visible'); }
    function toggleDesktopCollapse() { body.classList.toggle('sidebar-collapsed'); }
    if (menuToggle) { menuToggle.addEventListener('click', toggleMobileSidebar); }
    if (closeMenuBtn) { closeMenuBtn.addEventListener('click', toggleMobileSidebar); }
    if (collapseSidebarBtn) { collapseSidebarBtn.addEventListener('click', toggleDesktopCollapse); }
    
    mainMenu.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (link && link.hasAttribute('data-page')) {
            event.preventDefault();
            menuItems.forEach(item => item.classList.remove('active'));
            link.closest('li').classList.add('active');
            const page = link.getAttribute('data-page');
            loadContent(page);
            if (window.innerWidth <= 800 && body.classList.contains('sidebar-visible')) {
                toggleMobileSidebar();
            }
        }
    });

    loadContent('project-overview'); // โหลดหน้าแรก

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