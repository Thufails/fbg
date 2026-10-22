<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periode Laporan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 600px; margin-top: 50px; }
        .alert { display: none; }
        #successContent { display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Form Periode Laporan</h4>
            </div>
            <div class="card-body">
                <!-- Alert Messages -->
                <div id="successAlert" class="alert alert-success alert-dismissible fade show" role="alert">
                    <span id="successMessage">Data berhasil dikirim!</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                
                <div id="errorAlert" class="alert alert-danger alert-dismissible fade show" role="alert">
                    <span id="errorMessage">Terjadi kesalahan!</span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Form -->
                <form id="dateForm">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Awal</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>

                    <!--<div class="mb-3">-->
                    <!--    <label for="action_type" class="form-label">Tipe Aksi</label>-->
                    <!--    <select class="form-select" id="action_type" name="action_type" required>-->
                    <!--        <option value="">Pilih Aksi</option>-->
                    <!--        <option value="update_data">Update Data</option>-->
                    <!--        <option value="generate_report">Generate Report</option>-->
                    <!--        <option value="calculate_summary">Calculate Summary</option>-->
                    <!--    </select>-->
                    <!--</div>-->

                    <!--<div class="mb-3">-->
                    <!--    <label for="notes" class="form-label">Catatan (Opsional)</label>-->
                    <!--    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea>-->
                    <!--</div>-->

                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                        <span class="spinner-border spinner-border-sm me-2" style="display: none;" id="spinner"></span>
                        Generate Data
                    </button>
                </form>

                <!-- Success Content dengan Tombol Google Slides -->
                <div id="successContent" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h5>Proses Berhasil!</h5>
                    <p>Data telah diproses dan Google Sheet telah diupdate.</p>
                    <a href="#" id="slideLink" target="_blank" class="btn btn-success">
                        📊 Buka Google Slides
                    </a>
                    <button id="closeSuccess" class="btn btn-secondary ms-2">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('dateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const spinner = document.getElementById('spinner');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            const successContent = document.getElementById('successContent');
            
            // Reset tampilan
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';
            successContent.style.display = 'none';
            
            try {
                const formData = new FormData(this);
                const data = {
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    // action_type: formData.get('action_type'),
                    notes: formData.get('notes'),
                    submitted_at: new Date().toISOString(),
                    source: 'web_form'
                };
                
                const response = await fetch('process-dates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const responseText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Response tidak valid dari server: ' + responseText);
                }
                
                if (result.success) {
                    // Tampilkan alert success
                    document.getElementById('successMessage').textContent = result.message;
                    successAlert.style.display = 'block';
                    
                    // Tampilkan tombol Google Slides
                    const slideLink = document.getElementById('slideLink');
                    
                    // Set URL Google Slides
                    const googleSlideUrl = 'https://docs.google.com/presentation/d/1tbfWKYnOLBaCUT5jVOnrS_xj0tFh_6kxvIQBTjPUZTQ/edit';
                    slideLink.href = googleSlideUrl;
                    successContent.style.display = 'block';
                    
                    // Reset form
                    this.reset();
                } else {
                    throw new Error(result.error || 'Terjadi kesalahan');
                }
                
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('errorMessage').textContent = error.message;
                errorAlert.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });

        // Tombol tutup untuk success content
        document.getElementById('closeSuccess').addEventListener('click', function() {
            document.getElementById('successContent').style.display = 'none';
        });

        // Set default dates
        window.addEventListener('load', function() {
            const today = new Date().toISOString().split('T')[0];
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
            const oneWeekAgoStr = oneWeekAgo.toISOString().split('T')[0];
            
            document.getElementById('start_date').value = oneWeekAgoStr;
            document.getElementById('end_date').value = today;
        });
    </script>
</body>
</html>