    <!-- Vulnerable inline scripts for testing -->
    <script>
        // Exposed global variables for penetration testing
        window.currentUser = <?php echo json_encode($_SESSION ?? []); ?>;
        window.apiEndpoint = '../api/';
        
        // Initialize app when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔓 BullsCorp Payroll App loaded');
            console.log('🔓 Current session:', window.currentUser);
            console.log('🔓 Debug functions available in window.debug');
        });
    </script>
</body>
</html>