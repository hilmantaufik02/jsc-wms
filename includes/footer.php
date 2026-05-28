            </main>
        </div> <!-- End Main Content Wrapper -->
    </div> <!-- End Flex H-Screen Wrapper -->

    <script>
        // Toggle Dropdown (Notif / User Profile)
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const caret = document.getElementById('userCaret');
            
            // Close other dropdowns
            const allDropdowns = ['notifDropdown', 'userDropdown'];
            allDropdowns.forEach(did => {
                if(did !== id) {
                    const el = document.getElementById(did);
                    if(el && !el.classList.contains('hidden')) {
                        el.classList.add('hidden');
                        if (did === 'userDropdown' && caret) caret.classList.remove('rotate-180');
                    }
                }
            });

            // Toggle current dropdown
            if (dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('hidden');
                if (id === 'userDropdown' && caret) caret.classList.add('rotate-180');
            } else {
                dropdown.classList.add('hidden');
                if (id === 'userDropdown' && caret) caret.classList.remove('rotate-180');
            }
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            if (!document.getElementById('notifButton').contains(e.target) && !document.getElementById('notifDropdown').contains(e.target)) {
                const notif = document.getElementById('notifDropdown');
                if(notif && !notif.classList.contains('hidden')) notif.classList.add('hidden');
            }
            if (!document.getElementById('userButton').contains(e.target) && !document.getElementById('userDropdown').contains(e.target)) {
                const user = document.getElementById('userDropdown');
                const caret = document.getElementById('userCaret');
                if(user && !user.classList.contains('hidden')) {
                    user.classList.add('hidden');
                    if(caret) caret.classList.remove('rotate-180');
                }
            }
        });
    </script>
</body>
</html>
