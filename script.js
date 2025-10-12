document.addEventListener('DOMContentLoaded', function() {
    const menuLinks = document.querySelectorAll('.menu a');
    const contentDisplay = document.getElementById('content-display');
    const menuItems = document.querySelectorAll('.menu li');

    // ฟังก์ชันสำหรับโหลดเนื้อหา
    function loadContent(page) {
        contentDisplay.innerHTML = '<h1>กำลังโหลด...</h1>';

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

    // จัดการการคลิกเมนูหลัก
    menuLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            menuItems.forEach(item => item.classList.remove('active'));
            this.parentElement.classList.add('active');

            const page = this.getAttribute('data-page');
            loadContent(page);
        });
    });

    // โหลดเนื้อหาแรกเมื่อเปิดหน้าเว็บ
    loadContent('0-ubuntu-setup');

    // =============================================================
    // ===== เริ่มส่วนโค้ดใหม่สำหรับจัดการแท็บ (เพิ่มส่วนนี้เข้ามา) =====
    // =============================================================
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
    // =============================================================
    // ======================= จบส่วนโค้ดใหม่ =======================
    // =============================================================
});