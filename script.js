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

    // --- (ฟังก์ชัน populateStatistics อัปเดตใหม่) ---
    async function populateStatistics() {
        const statsPlaceholder = document.getElementById('stats-placeholder');
        if (!statsPlaceholder) return;
        statsPlaceholder.innerHTML = '<p>กำลังโหลดสถิติจาก Server...</p>';

        try {
            // 1. ยิง API ไปยัง Backend (vecskill-stat.bncc.ac.th)
            const response = await fetch('https://vecskill-stat.bncc.ac.th/api/stats'); 
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.statusText}`);
            }
            
            const allCounts = await response.json();

            // --- (เพิ่มใหม่) --- กรองหน้า 'project-overview' ออก ---
            const filteredCounts = allCounts.filter(item => item.page_name !== 'project-overview');
            // ---------------------------------------------------

            // 3. สร้าง HTML ตาราง (ใช้ filteredCounts)
            if (!filteredCounts || filteredCounts.length === 0) {
                statsPlaceholder.innerHTML = '<p>ยังไม่มีสถิติการเข้าชม (ยกเว้นหน้าภาพรวม)</p>';
                return;
            }

            let tableHTML = '<table class="spec-table">';
            tableHTML += '<thead><tr><th>อันดับ</th><th>ชื่อหน้า (Page ID)</th><th>จำนวนครั้งที่เข้าชม (รวม)</th></tr></thead>';
            tableHTML += '<tbody>';

            // 4. วนลูปด้วยข้อมูลที่กรองแล้ว
            filteredCounts.forEach((item, index) => {
                // ค้นหาชื่อเมนูที่แสดง (DisplayName) จากเมนู
                const menuLink = document.querySelector(`.menu a[data-page="${item.page_name}"] span`);
                const displayName = menuLink ? menuLink.textContent.trim() : item.page_name;

                tableHTML += `
                    <tr>
                        <td><strong>${index + 1}</strong></td>
                        <td>${displayName} (<code>${item.page_name}</code>)</td>
                        <td><strong>${item.views}</strong></td>
                    </tr>
                `;
            });

            tableHTML += '</tbody></table>';
            statsPlaceholder.innerHTML = tableHTML;

        } catch (error) {
            console.error('Failed to fetch stats:', error);
            statsPlaceholder.innerHTML = '<p style="color: red;">ไม่สามารถโหลดสถิติได้</p>';
        }
    }

    // --- (ฟังก์ชัน loadContent อัปเดตใหม่) ---
    function loadContent(page) {
        contentDisplay.innerHTML = '<h1><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</h1>';

        // --- View Counting Logic (Server-side) ---
        // ยิง API ไปยัง Backend เพื่อบันทึก Log
        fetch('https://vecskill-stat.bncc.ac.th/api/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ page: page })
        }).catch(err => {
            console.warn('Failed to track page view:', err.message);
        });
        // --- End View Counting Logic ---

        fetch(`pages/${page}.html`)
            .then(response => {
                if (!response.ok) { throw new Error(`ไม่พบไฟล์: ${page}.html`); }
                return response.text();
            })
            .then(data => {
                contentDisplay.innerHTML = data;
                
                // (เราลบตัวนับยอดวิวท้ายหน้าออกแล้ว)

                addCopyButtons(); // เรียกใช้ฟังก์ชันปุ่ม Copy

                // ตรวจสอบถ้าเป็นหน้าสถิติ ให้เรียกฟังก์ชันสร้างตาราง
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
    function toggleMobileSidebar() {
        body.classList.toggle('sidebar-visible');
    }
    function toggleDesktopCollapse() {
        body.classList.toggle('sidebar-collapsed');
    }
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