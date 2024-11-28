const input = document.querySelector('.file-input');
const divPreviewImg = document.querySelector('.previewDivImage');
const previewImg = document.querySelector('.previewImg');

if (input) {
    input.addEventListener('change', (e) => {
        let render = new FileReader();
        render.onload = () => {
            divPreviewImg.innerHTML = `
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary">
<button type="button" class="btn-close"></button>
    </span>

        <img src="${render.result}" alt="No image" class="previewImg h-100" />`
            const deleteInput = divPreviewImg.querySelector('.btn-close');
            deleteInput.addEventListener('click', () => clearInput());
        }

        render.readAsDataURL(e.target.files[0]);
    });

    const clearInput = () => {
        input.value = null;
        let childPreviewImg = divPreviewImg.querySelectorAll('*');
        childPreviewImg.forEach(e => {
            e.remove();
        })
    }
}