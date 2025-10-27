// Biến đếm chung để tạo ID duy nhất cho mỗi input file
let fileInputCounter = 0;

/**
 * Hàm hiển thị thông báo thay thế alert()
 * @param {string} message - Nội dung thông báo
 */
function displayFileLimitMessage(message) {
    const msgDiv = document.getElementById('file-limit-message');
    msgDiv.textContent = message;
    msgDiv.style.display = 'block';
    // Xóa thông báo sau 4 giây
    setTimeout(() => msgDiv.style.display = 'none', 4000); 
}

/**
 * Hàm cập nhật thông báo khi không có file nào được chọn
 */
function updateNoFileMessage() {
    const container = document.getElementById('combined-preview-container');
    const existingMessage = container.querySelector('span[style*="color"]');
    
    // Đếm số lượng preview item thực tế (bỏ qua thông báo)
    const previewItems = container.querySelectorAll('.preview-item');

    if (previewItems.length === 0) {
        if (!existingMessage) {
            const span = document.createElement('span');
            span.style.cssText = 'color: #999; font-style: italic;';
            span.textContent = 'Chưa có file nào được chọn.';
            container.appendChild(span);
        }
    } else {
        if (existingMessage) {
            existingMessage.remove();
        }
    }
}


/**
 * Hàm xử lý khi file được chọn (tạo preview)
 * @param {HTMLInputElement} inputElement - Input file element
 * @param {string} type - 'images' hoặc 'videos'
 * @param {number} fileIndex - Index của file (1-based) dùng cho tên hiển thị
 */
function handleFileChange(inputElement, type, fileIndex) {
    const file = inputElement.files[0];
    const uniqueId = inputElement.getAttribute('data-id');
    const container = document.getElementById('combined-preview-container');

    // 1. Xóa preview cũ hoặc thông báo "Chưa có file"
    const existingPreview = document.getElementById(`preview-${uniqueId}`);

    if (!file) { 
        if (existingPreview) {
            existingPreview.remove();
        }
        updateNoFileMessage(); // Cập nhật lại thông báo khi không còn file
        return; 
    }

    const reader = new FileReader();

    reader.onload = function(e) {
        // fileUrl là Data URL (Base64) - đây là điểm mấu chốt giúp xem trước mà không cần upload
        const fileUrl = e.target.result; 
        const previewElement = document.createElement('div');
        previewElement.id = `preview-${uniqueId}`;
        previewElement.className = 'preview-item';
        
        const fileTypeLabel = type === 'images' ? 'Ảnh' : 'Video';
        // Sửa: loại bỏ '++' thừa để tránh lỗi cú pháp
        const fileNameLabel = `${fileTypeLabel} ${fileIndex}`; 

        // Cập nhật: Bỏ thẻ <a> và thêm controls cho video để xem được ngay
        previewElement.innerHTML = `
            ${type === 'images' 
                ? `<img src="${fileUrl}" alt="${fileNameLabel}" class="preview-thumbnail">` 
                // Thêm 'controls' để video có thể được phát
                : `<video src="${fileUrl}" controlsList="nodownload" class="preview-thumbnail" controls></video>`
            }
            <span class="file-name">${fileNameLabel}</span>
        `;
        
        // Thêm/cập nhật preview vào container chung
        if(existingPreview) {
            container.replaceChild(previewElement, existingPreview);
        } else {
            container.appendChild(previewElement);
        }
        
        updateNoFileMessage(); // Cập nhật lại (ẩn) thông báo "Chưa có file"
    };

    reader.readAsDataURL(file);
}

/**
 * Hàm tạo khung input file mới và gán index
 * @param {string} name - 'images' hoặc 'videos'
 * @param {number} fileIndex - Index của file (1-based) dùng cho tên hiển thị
 */
function createFileInputGroup(name, fileIndex) { 
    const uniqueId = `file-${fileInputCounter++}`;
    
    const group = document.createElement('div');
    group.className = 'file-input-group';
    
    const input = document.createElement('input');
    input.type = 'file';
    input.name = name + '[]';
    input.setAttribute('data-id', uniqueId); 
    input.accept = name === 'images' ? 'image/*' : 'video/*';
    
    // Gán index và xử lý file change
    input.onchange = (e) => handleFileChange(e.target, name, fileIndex);

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-file-btn';
    removeBtn.textContent = '✖';
    removeBtn.onclick = function() { removeFileInput(this); };

    group.appendChild(input);
    group.appendChild(removeBtn);
    return group;
}

// Xóa khung input file VÀ preview
function removeFileInput(button) {
    const wrapper = button.closest('#image-wrapper') || button.closest('#video-wrapper');
    const group = button.parentNode;
    const inputElement = group.querySelector('input[type="file"]');
    const uniqueId = inputElement ? inputElement.getAttribute('data-id') : null;
    
    // 1. Xóa preview element từ container chung
    if (uniqueId) {
        const previewElement = document.getElementById(`preview-${uniqueId}`);
        if (previewElement) {
            previewElement.remove();
        }
    }

    // 2. Xóa input field hoặc reset
    if (wrapper.children.length > 1) {
        wrapper.removeChild(group);
    } else {
        // Nếu chỉ còn 1 field, reset giá trị
        if (inputElement) {
            inputElement.value = '';
        }
    }
    updateNoFileMessage(); // Cập nhật lại thông báo sau khi xóa
}

// Thêm input ảnh
function addImageInput() {
    let wrapper = document.getElementById('image-wrapper');
    // Index cho file mới là số lượng input hiện tại + 1
    const nextFileIndex = wrapper.children.length + 1;

    if (nextFileIndex <= 10) { 
        let newGroup = createFileInputGroup('images', nextFileIndex);
        wrapper.appendChild(newGroup);
    } else {
        displayFileLimitMessage('Bạn chỉ có thể thêm tối đa 10 ảnh.');
    }
}

// Thêm input video
function addVideoInput() {
    let wrapper = document.getElementById('video-wrapper');
    // Index cho file mới là số lượng input hiện tại + 1
    const nextFileIndex = wrapper.children.length + 1;

    // Kiểm tra xem phần tử có tồn tại không (chỉ tồn tại nếu có gói VIP)
    if (!wrapper) {
         displayFileLimitMessage('Bạn cần gói dịch vụ hỗ trợ video để thêm video.');
         return;
    }

    if (nextFileIndex <= 5) { 
        let newGroup = createFileInputGroup('videos', nextFileIndex);
        wrapper.appendChild(newGroup);
    } else {
        displayFileLimitMessage('Bạn chỉ có thể thêm tối đa 5 video.');
    }
}

// Khởi tạo ban đầu khi DOM đã load
document.addEventListener('DOMContentLoaded', function() {
    // Luôn đảm bảo có ít nhất 1 khung nhập cho Ảnh (index 1)
    const imageWrapper = document.getElementById('image-wrapper');
    if (imageWrapper && imageWrapper.children.length === 0) {
        imageWrapper.appendChild(createFileInputGroup('images', 1));
    }
    
    // Nếu cho phép video, đảm bảo có ít nhất 1 khung nhập cho Video (index 1)
    const videoWrapper = document.getElementById('video-wrapper');
    if (videoWrapper && videoWrapper.children.length === 0) {
        videoWrapper.appendChild(createFileInputGroup('videos', 1));
    }
    
    // Khởi tạo thông báo chưa có file
    updateNoFileMessage();
});
