# Database setup

1. Copy `.env.example` to `.env` and set the local MySQL username/password.
2. Run `schema.sql` in MySQL Workbench or with the MySQL client:

```bash
mysql -u root -p < schema.sql
```

3. Install dependencies with `npm install`.
4. Start the API with `npm start`.
5. Check `http://localhost:5000/api/health`. It should return `database: "connected"`.

The job-card form sends data to `POST /api/job-cards`. The main job card, complaints,
and damage items are saved in one transaction.
