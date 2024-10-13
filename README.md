1. **Install Dependencies:**
   Install Composer dependencies.
   ```bash
   docker run --rm \
       -v $(pwd):/app \
       -w /app \
       composer install --ignore-platform-reqs --no-scripts 
   ```

2. **Run Docker Compose::**
     ```bash
       ./vendor/bin/sail up


3. **Bash into container**
    ```bash
   docker exec -it fishing-lake-hvylya bash


4. **Inside container run migration && create link to storage**
    ```bash
   php artisan migrate 
   php artisan db:seed
   php artisan storage:link
      ```

5. **Inside container install dependencies**
    ```bash
   npm install
      ```

6. **Inside container run build**
    ```bash
   npm run prod
      ```
