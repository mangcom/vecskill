document.addEventListener('DOMContentLoaded', function() {
    // --- ส่วนของการเลือก Elements ---
    const menuLinks = document.querySelectorAll('.menu a');
    const contentDisplay = document.getElementById('content-display');
    const menuItems = document.querySelectorAll('.menu li');
    
    // Elements ใหม่สำหรับ Responsive Menu
    const menuToggle = document.getElementById('menu-toggle');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mainMenu = document.getElementById('main-menu');

    // --- ส่วนของฟังก์ชัน loadContent ---
    function loadContent(page) {
        contentDisplay.innerHTML = '<h1><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</h1>';

        fetch(`pages/${page}.html`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('ไม่พบไฟล์เนื้อหา');
                }
                return response.text();
            })
            .then(data => {
                contentDisplay.innerHTML = data;
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
                contentDisplay.innerHTML = '<h1>เกิดข้อผิดพลาด</h1><p>ไม่สามารถโหลดเนื้อหาได้ กรุณาตรวจสอบว่าไฟล์มีอยู่จริง</p>';
            });
    }

    // --- ฟังก์ชันสำหรับเปิด/ปิดเมนู ---
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-visible');
    }

    // --- การจัดการ Event Listeners ---

    // 1. คลิกที่ปุ่ม Hamburger เพื่อเปิดเมนู
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }
    
    // 2. คลิกที่ปุ่ม X เพื่อปิดเมนู
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', toggleSidebar);
    }

    // 3. คลิกที่ลิงก์ในเมนู
    mainMenu.addEventListener('click', function(event) {
        // ตรวจสอบว่าสิ่งที่คลิกคือลิงก์ <a>
        if (event.target.tagName === 'A') {
            event.preventDefault(); // ป้องกันการเปลี่ยนหน้าเว็บ

            // นำคลาส 'active' ออกจากทุกเมนู
            menuItems.forEach(item => item.classList.remove('active'));

            // เพิ่มคลาส 'active' ให้กับเมนูที่ถูกคลิก
            event.target.parentElement.classList.add('active');

            const page = event.target.getAttribute('data-page');
            loadContent(page);
            
            // ถ้าเป็นหน้าจอเล็ก ให้ปิดเมนูหลังจากเลือกหัวข้อแล้ว
            if (window.innerWidth <= 800) {
                toggleSidebar();
            }
        }
    });

    // --- โหลดเนื้อหาแรกเมื่อเปิดหน้าเว็บ ---
    loadContent('project-overview'); // เปลี่ยนเป็นหน้าที่ต้องการโหลดแรก

    // --- ส่วนจัดการแท็บ ---
    contentDisplay.addEventListener('click', function(event) {
        // ตรวจสอบว่าสิ่งที่คลิกคือปุ่มแท็บหรือไม่
        if (event.target.classList.contains('tablinks')) {
            const tabName = event.target.getAttribute('data-target-tab');
            
            // หา elements ที่เกี่ยวข้องภายใน contentDisplay เท่านั้น
            const tabContainer = event.target.closest('.tab').parentElement;
            const tabcontent = tabContainer.querySelectorAll(".tabcontent");
            const tablinks = tabContainer.querySelectorAll(".tablinks");

            // ซ่อนเนื้อหาของทุกแท็บ
            tabcontent.forEach(tab => {
                tab.style.display = "none";
            });

            // เอา class 'active' ออกจากปุ่มทุกปุ่ม
            tablinks.forEach(link => {
                link.classList.remove("active");
            });

            // แสดงเนื้อหาของแท็บที่เลือก และเพิ่ม class 'active' ให้กับปุ่มที่คลิก
            tabContainer.querySelector(`#${tabName}`).style.display = "block";
            event.target.classList.add("active");
        }
    });
});