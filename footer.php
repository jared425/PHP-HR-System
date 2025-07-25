<?php
/*
 * Footer Template - ModernTech HR System
 */
?>

            </div> <!-- Closing columns -->
        </div> <!-- Closing rows -->
    </div> <!-- Closing container -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // event listener just waits until the webpage is loaded before running the code
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar'); // fetches the sidebar created in header.php
            const body = document.body; // document is a representation of the entire html page
            const sidebarToggle = document.getElementById('sidebarToggle');// fetches the collapsing sidebar code from  header.php 
            
            // we are initializing the sidebar from cookie
            // firs the browser checks if the sidebar is collapsed or not
            if (document.cookie.includes('sidebarCollapsed=true')) {
                body.classList.add('collapsed'); // if true it adds the collapsed class to the body (collapses the sidebar)
                sidebar.classList.add('collapsed');
                updateToggleIcon(); // updates the toogle button
            }
            
            // Toggle functionality
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    body.classList.toggle('collapsed');
                    sidebar.classList.toggle('collapsed');
                    
                    // Set cookie to expire in 30 days
                    const expires = new Date();
                    expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));

                    // checks if the body has the class collapsed, if yes it saves sidebarcollapsed in the cookie so if the user changes pages the sidebar will still be collapsed
                    document.cookie = `sidebarCollapsed=${body.classList.contains('collapsed')}; expires=${expires.toUTCString()}; path=/`;
                    
                    updateToggleIcon();
                });
            }
            
            // this code just changes the arrow icon on the sidebar
            function updateToggleIcon() {
                const icon = sidebarToggle?.querySelector('i'); // looks for the icon tag in the button
                if (!icon) return;
                
                // checks if the sidebar is collapsed first
                if (body.classList.contains('collapsed')) {
                    icon.classList.remove('bi-chevron-double-left'); // removes the double arrow
                    icon.classList.add('bi-chevron-double-right'); // adds the double arrow
                } else {
                    icon.classList.remove('bi-chevron-double-right'); 
                    icon.classList.add('bi-chevron-double-left');
                }
            }
        });

        function confirmAction(message) {
            return confirm(message || 'Are you sure you want to do this?'); // confirm action message notification
        }
    </script>

    <!-- this just adds the js files to the webpage -->
    <?php if (isset($pageScripts)): ?>  <!-- checks if there are any page scripts to load -->
        <?php foreach ($pageScripts as $script): ?> <!-- loops through each script -->
            <script src="<?= htmlspecialchars($script) ?>"></script> <!-- inserts the script path and makes sure the path is safe with the htmlspecialchars() -->
        <?php endforeach; ?> 
    <?php endif; ?>
</body>
</html>