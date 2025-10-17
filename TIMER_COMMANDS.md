# Timer Control Commands

## Available Commands

### 1. Check Timer Status
```bash
php artisan timer:control status
```
Shows current timer status, end time, and remaining time.

---

### 2. Start Timer
```bash
php artisan timer:control start
```
Activates the countdown timer (continues from current end_time).

---

### 3. Stop Timer
```bash
php artisan timer:control stop
```
Pauses/stops the countdown timer from displaying on the website.

---

### 4. Restart Timer
```bash
# Restart with default 48 hours
php artisan timer:control restart

# Restart with custom hours (e.g., 24 hours)
php artisan timer:control restart 24

# Restart with 72 hours
php artisan timer:control restart 72
```
Resets the timer to start counting down from the specified hours.

---

### 5. Set Timer
```bash
# Set timer to 12 hours from now
php artisan timer:control set 12

# Set timer to 6 hours from now
php artisan timer:control set 6

# Set timer to 96 hours (4 days) from now
php artisan timer:control set 96
```
Sets the timer to a specific number of hours without restarting.

---

## Examples

### Example 1: Start a 24-hour flash sale
```bash
php artisan timer:control restart 24
```

### Example 2: Stop the timer (hide it from customers)
```bash
php artisan timer:control stop
```

### Example 3: Resume the timer
```bash
php artisan timer:control start
```

### Example 4: Check how much time is left
```bash
php artisan timer:control status
```

---

## How It Works

1. **Database-Driven**: Timer settings are stored in the `timer_settings` table
2. **Real-time Updates**: Frontend fetches timer status from the database
3. **Server-Side Control**: Use commands to control the timer from the server
4. **Automatic Display**: Timer shows/hides based on `is_active` status

---

## Notes

- Timer is synchronized across all users in real-time
- When stopped, timer value is preserved (can be resumed)
- When restarted, timer begins counting from the specified hours
- All changes take effect immediately on the website

