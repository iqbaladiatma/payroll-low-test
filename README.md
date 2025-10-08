# BullsCorp Payroll System - Vulnerable Web Application

## 🚨 DISCLAIMER - FOR EDUCATIONAL PURPOSES ONLY

**⚠️ WARNING: This application contains intentional security vulnerabilities and should NEVER be deployed in a production environment.**

---

## 📋 About

BullsCorp Payroll System is a deliberately vulnerable web application designed for penetration testing, vulnerability assessment, and cybersecurity education. This application simulates a real-world payroll management system with **intentionally implemented security flaws** at a **low security level**.

### 🎯 Purpose
- **Penetration Testing Practice**: Learn to identify and exploit common web vulnerabilities
- **Vulnerability Assessment Training**: Practice security assessment methodologies
- **Cybersecurity Education**: Understand how vulnerabilities manifest in real applications
- **Security Awareness**: Demonstrate the importance of secure coding practices

---

## 🔧 Features

### Core Functionality
- **Employee Management**: Add, edit, and manage employee records
- **Payroll Processing**: Calculate and generate monthly payslips
- **Attendance Tracking**: Clock in/out system with attendance reports
- **Request Management**: Leave requests, overtime, expense claims
- **User Authentication**: Login/registration system
- **Admin Dashboard**: Administrative controls and reporting
- **Profile Management**: Employee profile completion and editing

### 🔓 Intentional Vulnerabilities (Low Security Level)

#### 1. **SQL Injection**
- Direct string concatenation in database queries
- No prepared statements or input sanitization
- Exposed database errors with query details
- Multiple injection points across the application

#### 2. **Cross-Site Scripting (XSS)**
- Stored XSS in user inputs and descriptions
- Reflected XSS in search parameters
- No input validation or output encoding
- JavaScript execution in user-controlled data

#### 3. **Authentication & Session Management**
- Weak password policies
- Plain text password storage options
- Session fixation vulnerabilities
- No proper session timeout
- Backdoor authentication methods

#### 4. **Authorization Issues**
- Insecure direct object references
- Missing access controls
- Privilege escalation opportunities
- User impersonation via URL parameters

#### 5. **Information Disclosure**
- Exposed database credentials in source code
- Detailed error messages
- Debug information leakage
- Sensitive data in logs

#### 6. **File Upload Vulnerabilities**
- No file type validation
- Path traversal possibilities
- Unrestricted file uploads
- Executable file uploads allowed

#### 7. **Insecure Configuration**
- Default credentials
- Exposed admin interfaces
- Debug mode enabled
- Verbose error reporting

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional)

### Quick Setup
1. **Clone the repository**
   ```bash
   git clone https://github.com/your-repo/bullscorp-payroll.git
   cd bullscorp-payroll
   ```

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE bullscorp_payroll;"
   
   # Initialize database with sample data
   php scripts/init_database.php
   ```

3. **Configure Database**
   - Edit `config/database.php` with your database credentials
   - Default credentials: `root` / `(empty password)`

4. **Start Web Server**
   ```bash
   # Using PHP built-in server
   php -S localhost:8080
   
   # Or configure your web server to point to the project directory
   ```

5. **Access the Application**
   - Main Application: `http://localhost:8080`
   - Admin Panel: `http://localhost:8080/admin/`
   - Default Admin: `admin` / `admin123`

---

## 🎮 Usage Guide

### For Penetration Testers

#### Getting Started
1. **Reconnaissance**: Explore the application structure and functionality
2. **Vulnerability Discovery**: Use tools like Burp Suite, OWASP ZAP, or SQLMap
3. **Exploitation**: Practice exploiting discovered vulnerabilities
4. **Documentation**: Document findings and create proof-of-concepts

#### Recommended Testing Approach
1. **Automated Scanning**: Run vulnerability scanners
2. **Manual Testing**: Perform manual security testing
3. **Code Review**: Analyze source code for vulnerabilities
4. **Privilege Escalation**: Test for authorization bypasses
5. **Data Extraction**: Practice data exfiltration techniques

#### Sample Test Cases
- SQL injection in login forms
- XSS in submission descriptions
- File upload bypass techniques
- Session manipulation
- Direct object reference testing

### Default Accounts
```
Admin Account:
Username: admin
Password: admin123

Employee Account:
Username: employee
Password: employee123

Test Account:
Username: test
Password: test123
```

---

## 📁 Project Structure

```
bullscorp-payroll/
├── admin/                  # Admin panel files
│   ├── dashboard.php
│   ├── manage_employees.php
│   ├── manage_payroll.php
│   └── manage_submissions.php
├── api/                    # API endpoints
├── config/                 # Configuration files
│   ├── database.php
│   └── init_mysql.php
├── includes/               # Shared components
├── public/                 # Public access files
│   ├── login.php
│   ├── register.php
│   └── 404.php
├── scripts/                # Utility scripts
├── src/                    # Source code
│   └── controllers/
├── user/                   # User panel files
│   ├── dashboard.php
│   ├── profile.php
│   ├── payslip.php
│   └── submit_request.php
├── uploads/                # File upload directory
└── README.md
```

---

## 🔍 Vulnerability Assessment Checklist

### SQL Injection Testing
- [ ] Login forms
- [ ] Search functionality
- [ ] Employee management
- [ ] Payroll queries
- [ ] Submission filters

### XSS Testing
- [ ] User registration
- [ ] Profile updates
- [ ] Submission descriptions
- [ ] Search parameters
- [ ] Admin comments

### Authentication Testing
- [ ] Brute force attacks
- [ ] Password policies
- [ ] Session management
- [ ] Privilege escalation
- [ ] Account enumeration

### File Upload Testing
- [ ] File type validation
- [ ] File size limits
- [ ] Path traversal
- [ ] Malicious file uploads
- [ ] Directory listing

---

## 🛠️ Tools Compatibility

This application has been tested with:
- **Burp Suite Professional/Community**
- **OWASP ZAP**
- **SQLMap**
- **Nikto**
- **Nmap**
- **Metasploit**
- **Custom Python scripts**

---

## 📚 Learning Resources

### Recommended Reading
- OWASP Top 10 Web Application Security Risks
- Web Application Hacker's Handbook
- OWASP Testing Guide
- SANS Web Application Penetration Testing

### Online Courses
- OWASP WebGoat
- DVWA (Damn Vulnerable Web Application)
- PortSwigger Web Security Academy
- Cybrary Web Application Penetration Testing

---

## ⚖️ Legal Notice

**IMPORTANT**: This application is designed exclusively for:
- Educational purposes
- Authorized penetration testing
- Security research in controlled environments
- Cybersecurity training programs

**DO NOT USE** this application for:
- Unauthorized testing on systems you don't own
- Malicious activities
- Production deployments
- Any illegal purposes

Users are responsible for ensuring they have proper authorization before testing any systems.

---

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Add new vulnerabilities or improve existing ones
4. Update documentation
5. Submit a pull request

### Contribution Guidelines
- Maintain the "low security" level theme
- Document new vulnerabilities clearly
- Ensure educational value
- Test thoroughly before submitting

---

## 📞 Support & Contact

For questions, suggestions, or educational use:
- **Issues**: Use GitHub Issues for bug reports
- **Discussions**: Use GitHub Discussions for questions
- **Educational Inquiries**: Contact for training purposes

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**Additional Terms for Educational Use:**
- This software is provided "as is" for educational purposes only
- No warranty or support is provided
- Users assume all risks associated with use
- Not suitable for production environments

---

## 🏆 Credits & Acknowledgments

**Created by**: **Iqbal Muhammad Adiatma**

### Special Thanks
- OWASP Community for security guidelines
- PHP Security Consortium for best practices documentation
- Cybersecurity education community
- Open source security tools developers

### Inspiration
This project was inspired by the need for realistic, vulnerable applications for cybersecurity education and the importance of hands-on learning in penetration testing.

---

## 📈 Version History

### v1.0.0 (Current)
- Initial release with core payroll functionality
- Implemented intentional vulnerabilities across OWASP Top 10
- Complete employee management system
- Admin and user dashboards
- Comprehensive documentation

### Planned Features
- Additional vulnerability types
- More complex exploitation scenarios
- Integration with security testing tools
- Enhanced documentation and tutorials

---

**© 2024 Iqbal Muhammad Adiatma. All rights reserved.**

**Remember: With great power comes great responsibility. Use this knowledge to build a more secure digital world.**

---

*This project is dedicated to cybersecurity professionals, students, and researchers working to improve web application security through education and ethical hacking practices.*