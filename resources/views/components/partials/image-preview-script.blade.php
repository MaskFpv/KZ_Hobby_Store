@once
    @push('scripts')
        <script>
            // Tampilkan pratinjau gambar sebelum diupload
            function previewImage(input, targetId) {
                const target = document.getElementById(targetId);
                if (!target || !input.files || !input.files[0]) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    target.src = e.target.result;
                    target.classList.remove('d-none');
                };
                reader.readAsDataURL(input.files[0]);
            }
        </script>
    @endpush
@endonce
