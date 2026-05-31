        </div>
        
        <div class="footer">
            <p><i class="fas fa-code"></i> Created and maintained by <strong>Abdul Barique Ansari</strong> | © <?php echo date('Y'); ?> Police Diary System</p>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const toggle = document.querySelector('.menu-toggle');
                if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                if (sidebar) sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>