# Enhanced Student Profile Features

## Overview
The social platform now includes comprehensive student profile features that allow users to add and edit detailed information about themselves.

## New Profile Fields

### Personal Information
- **Bio**: A text area where students can describe themselves
- **Date of Birth**: Date picker for birthday
- **Gender**: Dropdown with options (Male, Female, Other, Prefer not to say)
- **Hometown**: Text field for hometown/city
- **Interests & Hobbies**: Text area for listing interests

### Academic Information
- **Student ID**: Unique student identifier
- **Course/Program**: The student's main course of study
- **Major/Specialization**: Specific area of focus within the course
- **Year Level**: Dropdown (1st Year, 2nd Year, 3rd Year, 4th Year, 5th Year, Graduate)

### Contact Information
- **Contact Number**: Phone number (existing field, now part of profile edit)
- **Emergency Contact Name**: Name of emergency contact person
- **Emergency Contact Phone**: Phone number of emergency contact

### Privacy Settings
- **Profile Visibility**: Control who can see the profile
  - **Students Only**: Only other students can view the profile
  - **Public**: Anyone can view the profile
  - **Private**: Only the user can view their own profile

## Features

### Profile Viewing
- Clean, organized layout with sections for different types of information
- Responsive design that works on mobile and desktop
- Avatar display with upload functionality
- Information is grouped logically (Personal, Academic, Contact)

### Profile Editing
- Toggle between view and edit modes
- Form validation for required fields
- All fields are optional except name and email
- Real-time preview of changes
- Success/error messages for updates

### Avatar Management
- Upload profile pictures
- Automatic resizing and optimization
- Preview before confirming changes
- Support for JPG, PNG, and GIF formats

### Privacy Controls
- Users can control who sees their profile information
- Emergency contact information is only visible to the profile owner
- Admin users can see warning information

## Database Schema

The following fields have been added to the `users` table:

```sql
ALTER TABLE users 
ADD COLUMN student_id VARCHAR(20) NULL,
ADD COLUMN date_of_birth DATE NULL,
ADD COLUMN gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
ADD COLUMN year_level ENUM('1st_year', '2nd_year', '3rd_year', '4th_year', '5th_year', 'graduate') NULL,
ADD COLUMN course VARCHAR(100) NULL,
ADD COLUMN major VARCHAR(100) NULL,
ADD COLUMN bio TEXT NULL,
ADD COLUMN hometown VARCHAR(100) NULL,
ADD COLUMN interests TEXT NULL,
ADD COLUMN emergency_contact_name VARCHAR(100) NULL,
ADD COLUMN emergency_contact_phone VARCHAR(15) NULL,
ADD COLUMN profile_visibility ENUM('public', 'students_only', 'private') DEFAULT 'students_only';
```

## Installation

1. **Run the database migration**:
   ```
   php public/setup_profile_fields.php
   ```

2. **Verify the installation**:
   - Visit any user profile page
   - If you're viewing your own profile, you should see an "Edit Profile" button
   - Click the button to access the new profile editing features

## Usage

### For Students
1. **View Profile**: Navigate to `profile.php` or click on your name/avatar
2. **Edit Profile**: Click the "Edit Profile" button when viewing your own profile
3. **Update Information**: Fill in any fields you want to share
4. **Set Privacy**: Choose who can see your profile information
5. **Save Changes**: Click "Save Changes" to update your profile

### For Administrators
- Admins can view all profile information regardless of privacy settings
- Warning information is displayed for users with disciplinary records
- Reporting functionality is available for inappropriate profiles

## Security Features

- **Input Validation**: All form inputs are validated and sanitized
- **Privacy Controls**: Users control their information visibility
- **Secure File Upload**: Avatar uploads are restricted to safe file types
- **SQL Injection Protection**: All database queries use prepared statements
- **XSS Protection**: All output is properly escaped

## Responsive Design

The profile interface is fully responsive and includes:
- Mobile-friendly layout
- Touch-friendly form controls
- Optimized image display
- Accessible form labels and controls

## Future Enhancements

Potential future additions could include:
- Social media links
- Academic achievements/awards
- Club memberships
- Skills and certifications
- Portfolio/project links
- Profile completion percentage
- Profile verification badges