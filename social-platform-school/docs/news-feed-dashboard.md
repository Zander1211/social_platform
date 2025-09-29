# News Feed Dashboard

The News Feed Dashboard is a comprehensive side panel that provides quick navigation and control over the news feed functionality in the social platform.

## Features

### 🔙 Back to News Feed
- **Prominent button** appears on all pages except the main news feed
- **Animated pulse effect** to draw attention
- **One-click navigation** back to the main news feed

### 📰 News Feed Navigation
- **All Posts** - View all posts in the feed (available to all users)
- **Recent Posts** - Posts from the last 24 hours (admin only)
- **Popular Posts** - Most engaged posts (available to all users)
- **Following** - Posts from users you follow (available to all users)

### ⚡ Quick Actions
- **New Post** - Toggle the post composer (admin only)
- **Search** - Focus on the search input
- **Chat** - Quick link to messaging

### 📊 Activity Stats (Admin Only)
- **Total Posts** - Count of all posts in the system
- **Today** - Posts created in the last 24 hours
- **My Posts** - Your personal post count (when logged in)
- **Restricted Access** - Only visible to admin users

### 🔍 Feed Filters (on news feed page)
- **Time-based filters**: All, This Week, This Month (available to all users)
- **Admin time filters**: Recent (24h) (admin only)
- **Role-based filters**: Admin Posts (admin only)
- **Content filters**: Show/Hide Images, Show/Hide Events (available to all users)

### 📈 Recent Activity (Admin Only)
- **Latest 3 posts** with author and timestamp
- **Click to scroll** to the corresponding post in the feed
- **Visual highlighting** when a post is found
- **Restricted Access** - Only visible to admin users

### 👤 User Account
- **User Information** - Display name, email, and role
- **Avatar Display** - Shows user profile picture or placeholder
- **Edit Profile** - Quick link to profile editing
- **Logout Button** - Secure logout with confirmation
- **Role Badge** - Visual indicator of user role (admin, user, etc.)

### 📄 Recent Posts Sidebar (Admin Only)
- **Recent Posts List** - Shows latest posts in right sidebar
- **Post Titles** - Quick overview of recent content
- **Timestamps** - When posts were created
- **Restricted Access** - Only visible to admin users

## Technical Implementation

### Files Created/Modified

1. **`src/View/news-feed-dashboard.php`** - Main dashboard component
2. **`src/View/header.php`** - Updated to include dashboard
3. **`src/Controller/PostController.php`** - Enhanced with filtering
4. **`public/assets/news-feed-dashboard.js`** - JavaScript functionality
5. **`public/assets/style.css`** - Enhanced styling
6. **`public/index.php`** - Updated to support filters

### Database Integration

The dashboard automatically detects and works with your existing database schema:
- **Modern schema**: Uses `title` and `content` fields
- **Legacy schema**: Falls back to `caption` field
- **Graceful degradation**: Works even with database connection issues

### Filter Parameters

The dashboard supports URL parameters for filtering:
- `?filter=recent` - Last 24 hours
- `?filter=week` - Last week
- `?filter=month` - Last month
- `?filter=admin` - Admin posts only
- `?filter=following` - Following (your posts)

### JavaScript Functions

Global functions available:
- `togglePostComposer()` - Show/hide post composer
- `focusSearch()` - Focus search input
- `applyFeedFilter(value)` - Apply feed filter
- `toggleImageFilter()` - Show/hide image posts
- `toggleEventFilter()` - Show/hide event posts

## Responsive Design

### Desktop (>900px)
- **Sticky positioning** - Dashboard stays in view while scrolling
- **Three-column layout** - Left sidebar, center feed, right sidebar
- **Full feature set** - All dashboard features available

### Mobile (≤900px)
- **Static positioning** - Dashboard scrolls with content
- **Single column layout** - Stacked vertically
- **Optimized buttons** - Smaller, touch-friendly interface
- **Simplified animations** - Reduced motion for performance

## Customization

### Styling
The dashboard uses CSS custom properties for easy theming:
```css
:root {
    --primary: #2563eb;
    --card: #ffffff;
    --muted: #6b7280;
}
```

### Adding New Filters
To add a new filter:

1. **Update PostController.php**:
```php
case 'your_filter':
    $whereConditions[] = "your_condition";
    break;
```

2. **Update dashboard select**:
```html
<option value="your_filter">Your Filter</option>
```

### Adding New Quick Actions
To add a new quick action:

1. **Add button in dashboard**:
```html
<button class="btn secondary quick-action-btn" onclick="yourFunction()">
    <i class="fa fa-icon"></i> Your Action
</button>
```

2. **Add JavaScript function**:
```javascript
function yourFunction() {
    // Your functionality
}
```

## Browser Support

- **Modern browsers**: Full functionality
- **IE11+**: Basic functionality (no CSS Grid fallback)
- **Mobile browsers**: Touch-optimized interface

## Performance

- **Lazy loading**: Dashboard loads after DOM ready
- **Efficient queries**: Optimized database queries
- **Minimal JavaScript**: Lightweight implementation
- **CSS animations**: Hardware-accelerated transitions

## Accessibility

- **Keyboard navigation**: All interactive elements accessible
- **Screen readers**: Proper ARIA labels and semantic HTML
- **High contrast**: Readable color combinations
- **Focus indicators**: Clear focus states

## Future Enhancements

Potential improvements:
- **Real-time updates** using WebSockets
- **Drag-and-drop** post reordering
- **Advanced filters** with multiple criteria
- **Saved filter presets**
- **Dashboard customization** options
- **Analytics integration**

## Troubleshooting

### Dashboard not showing
- Check that `news-feed-dashboard.php` exists
- Verify `header.php` includes the dashboard
- Ensure database connection is working

### Filters not working
- Check URL parameters are being passed
- Verify `PostController.php` has filter logic
- Check JavaScript console for errors

### Styling issues
- Verify `style.css` includes dashboard styles
- Check for CSS conflicts
- Ensure responsive breakpoints work

### JavaScript errors
- Check `news-feed-dashboard.js` is loaded
- Verify functions are in global scope
- Check browser console for errors

## Support

For issues or questions about the News Feed Dashboard:
1. Check this documentation
2. Review the code comments
3. Test with browser developer tools
4. Check database connectivity