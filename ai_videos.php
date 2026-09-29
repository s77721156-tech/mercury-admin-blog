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
            <a href="gallery.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg text-white font-medium transition border border-transparent">
                <i data-lucide="image" class="w-5 h-5"></i> Gallery
            </a>
            <a href="ai_videos.php" class="flex items-center gap-3 p-3 bg-white/20 rounded-lg text-white font-semibold transition border border-white/10">
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
                        AI Videos Dashboard
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <a href="../portfolio/graphic-design/ai-video" target="_blank" class="flex items-center gap-2 text-slate-500 hover:text-[#3b66b2] transition font-medium text-sm">
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

            <!-- Videos View -->
            <div id="view-videos" class="hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 font-display">Manage AI Videos</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="editingVideoId = null; showVideoForm()" class="bg-[#3b66b2] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#2f55a4] transition-colors flex items-center gap-2 shadow-sm shadow-[#3b66b2]/20">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Video
                        </button>
                    </div>
                </div>

                <div id="videos-grid-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm">
                        Loading videos...
                    </div>
                </div>
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

        // Utility functions
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function showAlert(msg, isError = false) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `
                <div class="p-4 rounded-xl ${isError ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'} flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-3">
                        <i data-lucide="${isError ? 'alert-circle' : 'check-circle'}" class="w-5 h-5"></i>
                        <span class="font-medium">${escapeHtml(msg)}</span>
                    </div>
                </div>
            `;
            container.classList.remove('hidden');
            lucide.createIcons();
            setTimeout(() => { container.classList.add('hidden'); }, 3000);
        }

        // Global variables
        let videos = [];
        let editingVideoId = null;

        // Videos
        function showVideosList() { 
            document.getElementById('view-videos').classList.remove('hidden');
            document.getElementById('view-video-form').classList.add('hidden');
        }
        function showVideoForm() {
            document.getElementById('view-videos').classList.add('hidden');
            document.getElementById('view-video-form').classList.remove('hidden');
            if (!editingVideoId) {
                document.getElementById('video-form').reset();
                document.getElementById('video-existing-path').value = '';
                document.getElementById('video-form-title').innerText = 'Add AI Video';
            }
        }

        async function fetchVideos() {
            try {
                const res = await fetch('index.php?route=gallery_videos');
                const json = await res.json();
                if (json.success) {
                    videos = json.data;
                    renderVideos(videos);
                }
            } catch (err) {
                showAlert('Failed to fetch videos', true);
            }
        }

        function renderVideos(data) {
            const container = document.getElementById('videos-grid-container');
            if (data.length === 0) {
                container.innerHTML = '<div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm">No videos found.</div>';
                return;
            }
            container.innerHTML = data.map(video => `
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col">
                    <div class="relative aspect-[9/16] bg-black overflow-hidden">
                        <video class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" src="${escapeHtml(video.video_path)}" muted playsinline></video>
                        
                        <div class="absolute inset-0 bg-gradient-to-b from-black/40 via-black/10 to-transparent opacity-100 transition-opacity duration-300 pointer-events-none z-10"></div>
                        
                        <div class="absolute top-0 left-0 right-0 p-3 flex justify-end gap-2 z-20">
                            <button onclick="editVideo(${video.id})" class="p-2.5 bg-white/90 backdrop-blur-md rounded-full text-slate-700 hover:text-[#3b66b2] hover:bg-white shadow-md transition pointer-events-auto transform hover:scale-110" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteVideo(${video.id})" class="p-2.5 bg-white/90 backdrop-blur-md rounded-full text-slate-700 hover:text-red-600 hover:bg-white shadow-md transition pointer-events-auto transform hover:scale-110" title="Delete">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                        
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-20">
                            <div class="p-4 bg-white/20 backdrop-blur-md rounded-full border border-white/30 shadow-lg group-hover:scale-110 group-hover:opacity-0 transition-all duration-300">
                                <i data-lucide="play" class="w-8 h-8 text-white ml-1 fill-white"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-5 flex flex-col flex-grow bg-white z-30">
                        <h3 class="font-extrabold text-slate-800 text-lg leading-tight line-clamp-2 mb-4 group-hover:text-[#3b66b2] transition-colors">${escapeHtml(video.title)}</h3>
                        
                        <div class="flex items-center justify-between mt-auto">
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">${escapeHtml(video.category)}</span>
                            <span class="text-xs font-bold uppercase px-3 py-1.5 rounded-lg border ${video.status === 'Active' ? 'text-green-700 bg-green-50 border-green-200' : 'text-slate-500 bg-slate-50 border-slate-200'}">${escapeHtml(video.status)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
            
            // Add hover play functionality to videos in cards
            const videosList = container.querySelectorAll('video');
            videosList.forEach(vid => {
                vid.addEventListener('mouseover', () => vid.play().catch(()=>{}));
                vid.addEventListener('mouseout', () => { vid.pause(); vid.currentTime = 0; });
            });
        }

        async function submitVideoForm(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('video-submit-btn');
            const progressContainer = document.getElementById('video-progress-container');
            const progressBar = document.getElementById('video-progress-bar');
            const progressText = document.getElementById('video-progress-text');
            
            const title = document.getElementById('video-title').value;
            const category = document.getElementById('video-category').value;
            const status = document.getElementById('video-status').value;
            const existingPath = document.getElementById('video-existing-path').value;
            const fileInput = document.getElementById('video-file');
            
            const file = fileInput.files.length > 0 ? fileInput.files[0] : null;
            
            const targetUrl = editingVideoId ? `index.php?route=gallery_videos/${editingVideoId}` : 'index.php?route=gallery_videos';
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Uploading...';
            lucide.createIcons();

            try {
                if (!file) {
                    const formData = new FormData();
                    formData.append('title', title);
                    formData.append('category', category);
                    formData.append('status', status);
                    formData.append('existingPath', existingPath);
                    
                    const res = await fetch(targetUrl, { method: 'POST', body: formData });
                    const json = await res.json();
                    if (res.ok && json.success) {
                        showAlert('Video saved successfully');
                        showVideosList();
                        fetchVideos();
                    } else {
                        showAlert(json.message || 'Error saving video', true);
                    }
                } else {
                    progressContainer.classList.remove('hidden');
                    
                    const chunkSize = 1024 * 1024; // 1MB chunks
                    const totalChunks = Math.ceil(file.size / chunkSize);
                    const uniqueUploadId = Date.now().toString() + Math.floor(Math.random() * 1000);
                    
                    for (let i = 0; i < totalChunks; i++) {
                        const start = i * chunkSize;
                        const end = Math.min(start + chunkSize, file.size);
                        const chunk = file.slice(start, end);
                        
                        const formData = new FormData();
                        formData.append('title', title);
                        formData.append('category', category);
                        formData.append('status', status);
                        formData.append('existingPath', existingPath);
                        formData.append('chunk', chunk);
                        formData.append('chunkIndex', i);
                        formData.append('totalChunks', totalChunks);
                        formData.append('uploadId', uniqueUploadId);
                        formData.append('fileName', file.name);
                        
                        const res = await fetch(targetUrl, { method: 'POST', body: formData });
                        
                        let json;
                        try {
                            json = await res.json();
                        } catch (parseErr) {
                            throw new Error('Server returned an invalid response. The chunk might be too large.');
                        }
                        
                        if (!res.ok || !json.success) {
                            throw new Error(json.message || `Upload failed at chunk ${i}`);
                        }
                        
                        const percent = Math.round(((i + 1) / totalChunks) * 100);
                        progressBar.style.width = percent + '%';
                        progressText.innerText = percent + '%';
                        
                        if (i === totalChunks - 1) {
                            showAlert('Video saved successfully');
                            showVideosList();
                            fetchVideos();
                        }
                    }
                }
            } catch (err) {
                showAlert('Upload Error: ' + err.message, true);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="save" class="w-5 h-5"></i> Save Video';
                progressContainer.classList.add('hidden');
                progressBar.style.width = '0%';
                progressText.innerText = '0%';
                lucide.createIcons();
            }
        }

        function editVideo(id) {
            const video = videos.find(v => v.id == id);
            if (!video) return;
            editingVideoId = id;
            document.getElementById('video-title').value = video.title || '';
            document.getElementById('video-category').value = video.category || '';
            document.getElementById('video-status').value = video.status || 'Active';
            document.getElementById('video-existing-path').value = video.video_path || '';
            document.getElementById('video-form-title').innerText = 'Edit Video';
            showVideoForm();
        }

        async function deleteVideo(id) {
            if (!confirm('Delete this video?')) return;
            try {
                const res = await fetch(`index.php?route=gallery_videos/${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.success) {
                    showAlert('Video deleted');
                    fetchVideos();
                }
            } catch (err) {
                showAlert('Error deleting video', true);
            }
        }

        // Init
        document.getElementById('view-videos').classList.remove('hidden');
        fetchVideos();
    </script>
</body>
</html>
