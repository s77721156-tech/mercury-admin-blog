<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['blog_admin_logged_in']) || $_SESSION['blog_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - Mercury Softech</title>
    <link rel="icon" href="https://mercurysoftech.in/icon.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Inter', sans-serif; }
        .hidden { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 flex min-h-screen">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-[#0b4a86] text-white flex flex-col shadow-xl sticky top-0 h-screen overflow-y-auto flex-shrink-0">
        <div class="p-6 border-b border-white/20 flex flex-col items-center">
            <img src="https://mercurysoftech.in/icon.png" alt="Mercury Softech" class="h-14 w-14 object-contain bg-white rounded-[20px] p-1.5 mb-3 shadow-md" onerror="this.style.display='none'">
            <span class="font-bold text-sm text-center">Mercury Softech<br>Admin</span>
        </div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="blog.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg text-white font-medium transition border border-transparent">
                <i data-lucide="file-text" class="w-5 h-5"></i> Blog
            </a>
            <a href="careers.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg text-white font-medium transition border border-transparent">
                <i data-lucide="briefcase" class="w-5 h-5"></i> Careers
            </a>
            <a href="gallery.php" class="flex items-center gap-3 p-3 bg-white/20 rounded-lg text-white font-semibold transition border border-white/10">
                <i data-lucide="image" class="w-5 h-5"></i> Gallery
            </a>
            <a href="ai_videos.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg text-white font-medium transition border border-transparent">
                <i data-lucide="video" class="w-5 h-5"></i> AI Video
            </a>
        </nav>
        <div class="p-4 border-t border-white/20 mt-auto">
            <a href="logout.php" class="flex items-center gap-3 p-3 hover:bg-red-500/20 hover:text-red-100 rounded-lg text-white font-medium transition border border-transparent">
                <i data-lucide="log-out" class="w-5 h-5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Header -->
        <header class="bg-white shadow-sm sticky top-0 z-40">
            <div class="px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="text-lg font-extrabold text-slate-800">
                        Gallery Dashboard
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <a href="../gallery" target="_blank" class="flex items-center gap-2 text-slate-500 hover:text-[#3b66b2] transition font-medium text-sm">
                            View Live Site <i data-lucide="external-link" class="w-4 h-4"></i>
                        </a>
                        <div class="h-6 w-px bg-slate-200"></div>
                        <a href="logout.php" class="flex items-center gap-2 text-red-500 hover:text-red-700 transition font-medium text-sm">
                            Logout <i data-lucide="log-out" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-6 lg:p-8 flex-grow w-full">
            <!-- Alerts container -->
            <div id="alert-container" class="mb-6 hidden"></div>

            <!-- Tabs removed -->

            <!-- Photos View -->
            <div id="view-photos" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 font-display">Manage Photos</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="editingPhotoId = null; showPhotoForm()" class="bg-[#3b66b2] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#2f55a4] transition-colors flex items-center gap-2 shadow-sm shadow-[#3b66b2]/20">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Photo
                        </button>
                    </div>
                </div>

                <div id="photos-grid-container" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm">
                        Loading photos...
                    </div>
                </div>
            </div>


            <!-- Photo Form View -->
            <div id="view-photo-form" class="hidden max-w-4xl">
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="showPhotosList()" class="text-slate-500 hover:text-[#3b66b2] font-medium transition flex items-center gap-1 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                    </button>
                    <h1 class="text-2xl font-extrabold text-slate-800" id="photo-form-title">Add Photo</h1>
                </div>

                <form id="photo-form" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col gap-6" onsubmit="submitPhotoForm(event)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Title <span class="text-red-500">*</span></label>
                            <input type="text" id="photo-title" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Team Meeting">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Category <span class="text-red-500">*</span></label>
                            <input type="text" id="photo-category" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Team meeting">
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-sm font-bold text-slate-700">Photo File (Upload) <span class="text-red-500">*</span></label>
                            <input type="file" id="photo-file" accept="image/*" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                            <p class="text-xs text-slate-500 mt-1">Leave empty to keep current file if editing.</p>
                            <input type="hidden" id="photo-existing-path">
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-sm font-bold text-slate-700">Status <span class="text-red-500">*</span></label>
                            <select id="photo-status" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-6 mt-2 justify-end">
                        <button type="submit" class="bg-[#3b66b2] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#2f55a4] transition shadow-md shadow-[#3b66b2]/20 flex items-center gap-2">
                            <i data-lucide="save" class="w-5 h-5"></i> Save Photo
                        </button>
                    </div>
                </form>
            </div>

            <!-- Video Form View -->
            <div id="view-video-form" class="hidden max-w-4xl">
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="showVideosList()" class="text-slate-500 hover:text-[#3b66b2] font-medium transition flex items-center gap-1 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                    </button>
                    <h1 class="text-2xl font-extrabold text-slate-800" id="video-form-title">Add Video</h1>
                </div>

                <form id="video-form" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col gap-6" onsubmit="submitVideoForm(event)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Title <span class="text-red-500">*</span></label>
                            <input type="text" id="video-title" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Annual Event Highlights">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Category <span class="text-red-500">*</span></label>
                            <input type="text" id="video-category" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Team meeting">
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-sm font-bold text-slate-700">Video File (Upload) <span class="text-red-500">*</span></label>
                            <input type="file" id="video-file" accept="video/*" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                            <p class="text-xs text-slate-500 mt-1">Leave empty to keep current file if editing.</p>
                            <input type="hidden" id="video-existing-path">
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-sm font-bold text-slate-700">Status <span class="text-red-500">*</span></label>
                            <select id="video-status" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-6 mt-2 justify-end">
                        <button type="submit" id="video-submit-btn" class="bg-[#3b66b2] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#2f55a4] transition shadow-md shadow-[#3b66b2]/20 flex items-center gap-2">
                            <i data-lucide="save" class="w-5 h-5"></i> Save Video
                        </button>
                    </div>
                    <div id="video-progress-container" class="hidden mt-4">
                        <div class="text-sm font-bold text-slate-700 mb-1 flex justify-between">
                            <span>Uploading...</span>
                            <span id="video-progress-text">0%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2.5">
                            <div id="video-progress-bar" class="bg-[#3b66b2] h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        const alertContainer = document.getElementById('alert-container');
        let photos = [];
        let videos = [];
        let editingPhotoId = null;
        let editingVideoId = null;

        function showAlert(message, isError = false) {
            alertContainer.className = `p-4 rounded-xl mb-6 border font-medium flex items-center gap-2 text-sm ${isError ? 'bg-red-50 text-red-600 border-red-200' : 'bg-green-50 text-green-600 border-green-200'}`;
            alertContainer.innerHTML = `<i data-lucide="${isError ? 'alert-circle' : 'check-circle-2'}" class="w-5 h-5"></i> ${message}`;
            lucide.createIcons();
            alertContainer.classList.remove('hidden');
            setTimeout(() => alertContainer.classList.add('hidden'), 5000);
        }

        function escapeHtml(unsafe) {
            return (unsafe || '').toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function switchTab(tab) {
            document.getElementById('view-photos').classList.add('hidden');
            document.getElementById('view-videos').classList.add('hidden');
            document.getElementById('view-photo-form').classList.add('hidden');
            document.getElementById('view-video-form').classList.add('hidden');
            
            document.getElementById('tab-photos').className = 'px-4 py-2 font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent';
            document.getElementById('tab-videos').className = 'px-4 py-2 font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent';

            if (tab === 'photos') {
                document.getElementById('view-photos').classList.remove('hidden');
                document.getElementById('tab-photos').className = 'px-4 py-2 font-bold text-[#3b66b2] border-b-2 border-[#3b66b2]';
                fetchPhotos();
            } else {
                document.getElementById('view-videos').classList.remove('hidden');
                document.getElementById('tab-videos').className = 'px-4 py-2 font-bold text-[#3b66b2] border-b-2 border-[#3b66b2]';
                fetchVideos();
            }
        }

        // Photos
        function showPhotosList() { switchTab('photos'); }
        function showPhotoForm() {
            document.getElementById('view-photos').classList.add('hidden');
            document.getElementById('view-photo-form').classList.remove('hidden');
            if (!editingPhotoId) {
                document.getElementById('photo-form').reset();
                document.getElementById('photo-existing-path').value = '';
                document.getElementById('photo-form-title').innerText = 'Add Photo';
            }
        }

        async function fetchPhotos() {
            try {
                const res = await fetch('index.php?route=gallery_photos');
                const json = await res.json();
                if (json.success) {
                    photos = json.data;
                    renderPhotos(photos);
                }
            } catch (err) {
                showAlert('Failed to fetch photos', true);
            }
        }

        function renderPhotos(data) {
            const container = document.getElementById('photos-grid-container');
            if (data.length === 0) {
                container.innerHTML = '<div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm">No photos found.</div>';
                return;
            }
            container.innerHTML = data.map(photo => `
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col">
                    <div class="relative aspect-square bg-slate-100 overflow-hidden">
                        <img src="${escapeHtml(photo.image_path)}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                        
                        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none z-10"></div>
                        
                        <div class="absolute top-0 left-0 right-0 p-3 flex justify-end gap-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <button onclick="editPhoto(${photo.id})" class="p-2.5 bg-white/90 backdrop-blur-md rounded-full text-slate-700 hover:text-[#3b66b2] hover:bg-white shadow-md transition pointer-events-auto transform hover:scale-110" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deletePhoto(${photo.id})" class="p-2.5 bg-white/90 backdrop-blur-md rounded-full text-slate-700 hover:text-red-600 hover:bg-white shadow-md transition pointer-events-auto transform hover:scale-110" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-5 flex flex-col flex-grow bg-white z-30">
                        <h3 class="font-extrabold text-slate-800 text-base leading-tight line-clamp-2 mb-4 group-hover:text-[#3b66b2] transition-colors">${escapeHtml(photo.title)}</h3>
                        
                        <div class="flex flex-col gap-2 mt-auto">
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 inline-block w-fit">${escapeHtml(photo.category)}</span>
                            <span class="text-xs font-bold uppercase px-3 py-1.5 rounded-lg border inline-block w-fit ${photo.status === 'Active' ? 'text-green-700 bg-green-50 border-green-200' : 'text-slate-500 bg-slate-50 border-slate-200'}">${escapeHtml(photo.status)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        async function submitPhotoForm(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('title', document.getElementById('photo-title').value);
            formData.append('category', document.getElementById('photo-category').value);
            formData.append('status', document.getElementById('photo-status').value);
            formData.append('existingPath', document.getElementById('photo-existing-path').value);
            
            const fileInput = document.getElementById('photo-file');
            if (fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);
            }
            
            const targetUrl = editingPhotoId ? `index.php?route=gallery_photos/${editingPhotoId}` : 'index.php?route=gallery_photos';
            
            try {
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    showAlert('Photo saved successfully');
                    showPhotosList();
                } else {
                    showAlert(json.message, true);
                }
            } catch (err) {
                showAlert('Network error', true);
            }
        }

        function editPhoto(id) {
            const photo = photos.find(p => p.id == id);
            if (!photo) return;
            editingPhotoId = id;
            document.getElementById('photo-title').value = photo.title || '';
            document.getElementById('photo-category').value = photo.category || '';
            document.getElementById('photo-status').value = photo.status || 'Active';
            document.getElementById('photo-existing-path').value = photo.image_path || '';
            document.getElementById('photo-form-title').innerText = 'Edit Photo';
            showPhotoForm();
        }

        async function deletePhoto(id) {
            if (!confirm('Delete this photo?')) return;
            try {
                const res = await fetch(`index.php?route=gallery_photos/${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.success) {
                    showAlert('Photo deleted');
                    fetchPhotos();
                }
            } catch (err) {
                showAlert('Error deleting photo', true);
            }
        }

        // Init
        fetchPhotos();
    </script>
</body>
</html>
