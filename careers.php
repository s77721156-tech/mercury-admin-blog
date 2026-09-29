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
    <title>Careers Management - Mercury Softech</title>
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
            <a href="careers.php" class="flex items-center gap-3 p-3 bg-white/20 rounded-lg text-white font-semibold transition border border-white/10">
                <i data-lucide="briefcase" class="w-5 h-5"></i> Careers
            </a>
            <a href="gallery.php" class="flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg text-white font-medium transition border border-transparent">
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
                        Careers Dashboard
                    </div>
                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <a href="../careers" target="_blank" class="flex items-center gap-2 text-slate-500 hover:text-[#3b66b2] transition font-medium text-sm">
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

            <!-- Tabs -->
            <div class="flex items-center gap-4 border-b border-slate-200 mb-6">
                <button onclick="switchTab('jobs')" id="tab-jobs" class="px-4 py-2 font-bold text-[#3b66b2] border-b-2 border-[#3b66b2]">Jobs</button>
                <button onclick="switchTab('applications')" id="tab-applications" class="px-4 py-2 font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent">Applications</button>
            </div>

            <!-- Jobs View -->
            <div id="view-jobs" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 font-display">Manage Jobs</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="showJobForm()" class="bg-[#3b66b2] text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-[#2f55a4] transition-colors flex items-center gap-2 shadow-sm shadow-[#3b66b2]/20">
                            <i data-lucide="plus" class="w-4 h-4"></i> Add Job
                        </button>
                    </div>
                </div>

                <!-- Jobs Grid -->
                <div id="jobs-grid-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm">
                        Loading jobs...
                    </div>
                </div>
            </div>

            <!-- Applications View -->
            <div id="view-applications" class="hidden space-y-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 font-display">Job Applications</h1>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-1/4">Applicant</th>
                                    <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-1/4">Job</th>
                                    <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-1/4">Resume</th>
                                    <th class="px-6 py-4 text-xs font-black text-slate-400 uppercase tracking-widest w-1/4">Status</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="applications-table-body">
                                <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">Loading applications...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Job Form View -->
            <div id="view-job-form" class="hidden max-w-4xl">
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="showJobsList()" class="text-slate-500 hover:text-[#3b66b2] font-medium transition flex items-center gap-1 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                    </button>
                    <h1 class="text-2xl font-extrabold text-slate-800" id="job-form-title">Create Job</h1>
                </div>

                <form id="job-form" class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 flex flex-col gap-6" onsubmit="submitJobForm(event)">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Job Title <span class="text-red-500">*</span></label>
                            <input type="text" id="job-title" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Frontend Developer">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Department <span class="text-red-500">*</span></label>
                            <input type="text" id="job-department" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. Engineering">
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Type <span class="text-red-500">*</span></label>
                            <select id="job-type" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                                <option value="Full-Time">Full-Time</option>
                                <option value="Part-Time">Part-Time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-bold text-slate-700">Experience <span class="text-red-500">*</span></label>
                            <input type="text" id="job-experience" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="e.g. 2-4 Years">
                        </div>
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <label class="text-sm font-bold text-slate-700">Status <span class="text-red-500">*</span></label>
                            <select id="job-status" class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition">
                                <option value="Active">Active</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Description <span class="text-red-500">*</span></label>
                        <textarea id="job-description" rows="3" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="Job description..."></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-bold text-slate-700">Requirements (one per line) <span class="text-red-500">*</span></label>
                        <textarea id="job-requirements" rows="4" required class="bg-slate-50 border-transparent focus:bg-white border focus:border-[#3b66b2] rounded-xl p-3 outline-none focus:ring-2 focus:ring-[#3b66b2]/20 transition" placeholder="React.js&#10;Node.js&#10;3+ years experience"></textarea>
                    </div>

                    <div class="flex items-center gap-4 border-t border-slate-100 pt-6 mt-2 justify-end">
                        <button type="submit" id="submit-job-btn" class="bg-[#3b66b2] text-white px-8 py-3 rounded-xl font-bold hover:bg-[#2f55a4] transition shadow-md shadow-[#3b66b2]/20 flex items-center gap-2">
                            <i data-lucide="save" class="w-5 h-5"></i> Save Job
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        const alertContainer = document.getElementById('alert-container');
        let jobs = [];
        let editingJobId = null;

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
            document.getElementById('view-jobs').classList.add('hidden');
            document.getElementById('view-applications').classList.add('hidden');
            document.getElementById('view-job-form').classList.add('hidden');
            
            document.getElementById('tab-jobs').className = 'px-4 py-2 font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent';
            document.getElementById('tab-applications').className = 'px-4 py-2 font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent';

            if (tab === 'jobs') {
                document.getElementById('view-jobs').classList.remove('hidden');
                document.getElementById('tab-jobs').className = 'px-4 py-2 font-bold text-[#3b66b2] border-b-2 border-[#3b66b2]';
                fetchJobs();
            } else {
                document.getElementById('view-applications').classList.remove('hidden');
                document.getElementById('tab-applications').className = 'px-4 py-2 font-bold text-[#3b66b2] border-b-2 border-[#3b66b2]';
                fetchApplications();
            }
        }

        // Jobs
        function showJobsList() {
            switchTab('jobs');
        }

        function showJobForm() {
            document.getElementById('view-jobs').classList.add('hidden');
            document.getElementById('view-job-form').classList.remove('hidden');
            if (!editingJobId) {
                document.getElementById('job-form').reset();
                document.getElementById('job-form-title').innerText = 'Create Job';
            }
        }

        async function fetchJobs() {
            try {
                const res = await fetch('index.php?route=jobs');
                const json = await res.json();
                if (json.success) {
                    jobs = json.data;
                    renderJobs(jobs);
                }
            } catch (err) {
                showAlert('Failed to fetch jobs', true);
            }
        }

        function renderJobs(data) {
            const container = document.getElementById('jobs-grid-container');
            if (data.length === 0) {
                container.innerHTML = '<div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm flex flex-col items-center"><i data-lucide="briefcase" class="w-12 h-12 text-slate-300 mb-3"></i> No jobs found. Add one above.</div>';
                lucide.createIcons();
                return;
            }
            
            container.innerHTML = data.map(job => `
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group relative">
                    <!-- Accent Header -->
                    <div class="h-2 w-full bg-gradient-to-r ${job.status === 'Active' ? 'from-green-400 to-emerald-500' : 'from-slate-300 to-slate-400'}"></div>
                    
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex justify-between items-start gap-4 mb-3">
                            <h3 class="font-extrabold text-slate-800 text-lg leading-tight group-hover:text-[#3b66b2] transition-colors">${escapeHtml(job.title)}</h3>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2.5 py-1 rounded-md border ${job.status === 'Active' ? 'text-green-700 bg-green-50 border-green-200' : 'text-slate-500 bg-slate-50 border-slate-200'}">
                                ${escapeHtml(job.status)}
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="building-2" class="w-4 h-4 text-slate-400"></i>
                                <span class="font-medium">${escapeHtml(job.department)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                                <span class="font-medium">${escapeHtml(job.type)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <i data-lucide="graduation-cap" class="w-4 h-4 text-slate-400"></i>
                                <span class="font-medium">${escapeHtml(job.experience || 'Not specified')}</span>
                            </div>
                        </div>
                        
                        <p class="text-xs text-slate-500 line-clamp-3 mb-4 leading-relaxed flex-grow border-t border-slate-100 pt-4">
                            ${escapeHtml(job.description || 'No description available.')}
                        </p>
                        
                        <!-- Actions -->
                        <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button onclick="editJob(${job.id})" class="flex-1 py-2 text-sm font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors border border-blue-100 flex items-center justify-center gap-2" title="Edit">
                                <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                            </button>
                            <button onclick="deleteJob(${job.id})" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100" title="Delete">
                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }

        async function submitJobForm(e) {
            e.preventDefault();
            const payload = {
                title: document.getElementById('job-title').value,
                department: document.getElementById('job-department').value,
                type: document.getElementById('job-type').value,
                experience: document.getElementById('job-experience').value,
                description: document.getElementById('job-description').value,
                requirements: document.getElementById('job-requirements').value,
                status: document.getElementById('job-status').value,
            };
            
            const targetUrl = editingJobId ? `index.php?route=jobs/${editingJobId}` : 'index.php?route=jobs';
            
            try {
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    showAlert('Job saved successfully');
                    showJobsList();
                } else {
                    showAlert(json.message, true);
                }
            } catch (err) {
                showAlert('Network error', true);
            }
        }

        function editJob(id) {
            const job = jobs.find(j => j.id == id);
            if (!job) return;
            editingJobId = id;
            document.getElementById('job-title').value = job.title || '';
            document.getElementById('job-department').value = job.department || '';
            document.getElementById('job-type').value = job.type || '';
            document.getElementById('job-experience').value = job.experience || '';
            document.getElementById('job-description').value = job.description || '';
            document.getElementById('job-requirements').value = job.requirements || '';
            document.getElementById('job-status').value = job.status || '';
            document.getElementById('job-form-title').innerText = 'Edit Job';
            showJobForm();
        }

        async function deleteJob(id) {
            if (!confirm('Delete this job?')) return;
            try {
                const res = await fetch(`index.php?route=jobs/${id}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.success) {
                    showAlert('Job deleted');
                    fetchJobs();
                }
            } catch (err) {
                showAlert('Error deleting job', true);
            }
        }

        async function deleteApplication(id) {
            if (confirm('Are you sure you want to delete this application?')) {
                try {
                    const res = await fetch(`index.php?route=applications/${id}`, { method: 'DELETE' });
                    const json = await res.json();
                    if (json.success) {
                        showAlert('Application deleted successfully');
                        fetchApplications();
                    } else {
                        showAlert(json.message || 'Failed to delete application', true);
                    }
                } catch (err) {
                    showAlert('Error deleting application', true);
                }
            }
        }

        // Applications
        async function fetchApplications() {
            try {
                const res = await fetch('index.php?route=applications');
                const json = await res.json();
                if (json.success) {
                    renderApplications(json.data);
                }
            } catch (err) {
                showAlert('Failed to fetch applications', true);
            }
        }

        function renderApplications(data) {
            const tbody = document.getElementById('applications-table-body');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">No applications found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(app => `
                <tr class="hover:bg-slate-50/50">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">${escapeHtml(app.name)}</div>
                        <div class="text-xs text-slate-400 mt-1">${escapeHtml(app.email)} | ${escapeHtml(app.phone)}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-slate-700">${escapeHtml(app.job_title || 'Unknown Job (Deleted)')}</div>
                        <div class="text-xs text-slate-400 mt-1">${new Date(app.created_at).toLocaleDateString()}</div>
                    </td>
                    <td class="px-6 py-4">
                        ${app.resume_link ? `<a href="${escapeHtml(app.resume_link)}" target="_blank" class="text-[#3b66b2] hover:underline text-sm font-medium"><i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i> View Resume</a>` : '<span class="text-slate-400 text-sm">No resume</span>'}
                    </td>
                    <td class="px-6 py-4">
                        <select onchange="updateApplicationStatus(${app.id}, this.value)" class="text-sm font-bold rounded-lg border-slate-200 bg-white px-3 py-1.5 focus:ring-[#3b66b2] outline-none
                            ${app.status === 'Selected' ? 'text-green-600' : (app.status === 'Rejected' ? 'text-red-600' : 'text-slate-600')}">
                            <option value="Pending" ${app.status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Reviewed" ${app.status === 'Reviewed' ? 'selected' : ''}>Reviewed</option>
                            <option value="Selected" ${app.status === 'Selected' ? 'selected' : ''}>Selected</option>
                            <option value="Rejected" ${app.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="deleteApplication(${app.id})" class="text-slate-400 hover:text-red-500 transition tooltip" title="Delete">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            lucide.createIcons();
        }

        async function updateApplicationStatus(id, status) {
            try {
                const res = await fetch(`index.php?route=applications/${id}&update_status=1`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ status })
                });
                const json = await res.json();
                if (json.success) {
                    showAlert('Status updated');
                    fetchApplications();
                } else {
                    showAlert('Failed to update status', true);
                }
            } catch (err) {
                showAlert('Error', true);
            }
        }

        // Init
        fetchJobs();
    </script>
</body>
</html>
