-- Create sessions table for Laravel session management
-- This table is used to store user sessions when using database session driver

IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='sessions' AND xtype='U')
BEGIN
    CREATE TABLE [dbo].[sessions] (
        [id] NVARCHAR(255) NOT NULL PRIMARY KEY,
        [user_id] BIGINT NULL,
        [ip_address] NVARCHAR(45) NULL,
        [user_agent] NTEXT NULL,
        [payload] NTEXT NOT NULL,
        [last_activity] INT NOT NULL
    );
    
    -- Create indexes for better performance
    CREATE INDEX [sessions_user_id_index] ON [dbo].[sessions] ([user_id]);
    CREATE INDEX [sessions_last_activity_index] ON [dbo].[sessions] ([last_activity]);
    
    PRINT 'Sessions table created successfully';
END
ELSE
BEGIN
    PRINT 'Sessions table already exists';
END

-- Optional: Add foreign key constraint if users table exists
-- Uncomment the following lines if you want to enforce referential integrity
/*
IF EXISTS (SELECT * FROM sysobjects WHERE name='users' AND xtype='U')
BEGIN
    IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_sessions_user_id')
    BEGIN
        ALTER TABLE [dbo].[sessions]
        ADD CONSTRAINT [FK_sessions_user_id] 
        FOREIGN KEY ([user_id]) REFERENCES [dbo].[users]([id]) 
        ON DELETE CASCADE;
        
        PRINT 'Foreign key constraint added to sessions table';
    END
END
*/