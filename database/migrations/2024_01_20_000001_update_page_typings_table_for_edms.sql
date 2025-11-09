-- EDMS Page Typing Schema Updates
-- Run this SQL script manually in SQL Server Management Studio

-- Check if page_typings table exists, if not create it
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='pagetypings' AND xtype='U')
BEGIN
    CREATE TABLE [dbo].[pagetypings] (
        [id] [bigint] IDENTITY(1,1) NOT NULL,
        [file_indexing_id] [bigint] NOT NULL,
        [scanning_id] [bigint] NULL,
        [page_no] [int] NOT NULL,
        [page_type] [nvarchar](100) NOT NULL,
        [subtype] [nvarchar](100) NULL,
        [serial_no] [int] NOT NULL,
        [page_code] [nvarchar](100) NULL,
        [file_path] [nvarchar](500) NOT NULL,
        [source] [nvarchar](50) NOT NULL DEFAULT 'initial',
        [qc_overridden] [bit] NOT NULL DEFAULT 0,
        [qc_override_note] [nvarchar](500) NULL,
        [typed_by] [bigint] NULL,
        [qc_reviewed_by] [bigint] NULL,
        [qc_reviewed_at] [datetime2](7) NULL,
        [deleted_at] [datetime2](7) NULL,
        [created_at] [datetime2](7) NULL,
        [updated_at] [datetime2](7) NULL,
        CONSTRAINT [PK_pagetypings] PRIMARY KEY CLUSTERED ([id] ASC)
    )
    
    -- Add foreign key constraints
    ALTER TABLE [dbo].[pagetypings] ADD CONSTRAINT [FK_pagetypings_file_indexings] 
        FOREIGN KEY([file_indexing_id]) REFERENCES [dbo].[file_indexings] ([id])
    
    ALTER TABLE [dbo].[pagetypings] ADD CONSTRAINT [FK_pagetypings_scannings] 
        FOREIGN KEY([scanning_id]) REFERENCES [dbo].[scannings] ([id])
    
    ALTER TABLE [dbo].[pagetypings] ADD CONSTRAINT [FK_pagetypings_users_typed_by] 
        FOREIGN KEY([typed_by]) REFERENCES [dbo].[users] ([id])
    
    ALTER TABLE [dbo].[pagetypings] ADD CONSTRAINT [FK_pagetypings_users_qc_reviewed_by] 
        FOREIGN KEY([qc_reviewed_by]) REFERENCES [dbo].[users] ([id])
    
    PRINT 'Created pagetypings table with all required fields'
END
ELSE
BEGIN
    PRINT 'pagetypings table already exists, checking for missing columns...'
    
    -- Add missing columns if they don't exist
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'source')
    BEGIN
        ALTER TABLE [dbo].[pagetypings] ADD [source] [nvarchar](50) NOT NULL DEFAULT 'initial'
        PRINT 'Added source column'
    END
    
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'qc_overridden')
    BEGIN
        ALTER TABLE [dbo].[pagetypings] ADD [qc_overridden] [bit] NOT NULL DEFAULT 0
        PRINT 'Added qc_overridden column'
    END
    
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'qc_override_note')
    BEGIN
        ALTER TABLE [dbo].[pagetypings] ADD [qc_override_note] [nvarchar](500) NULL
        PRINT 'Added qc_override_note column'
    END
    
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'deleted_at')
    BEGIN
        ALTER TABLE [dbo].[pagetypings] ADD [deleted_at] [datetime2](7) NULL
        PRINT 'Added deleted_at column for soft deletes'
    END
    
    -- Rename columns if they have different names
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'page_number')
    AND NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'page_no')
    BEGIN
        EXEC sp_rename 'pagetypings.page_number', 'page_no', 'COLUMN'
        PRINT 'Renamed page_number to page_no'
    END
    
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'page_subtype')
    AND NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'subtype')
    BEGIN
        EXEC sp_rename 'pagetypings.page_subtype', 'subtype', 'COLUMN'
        PRINT 'Renamed page_subtype to subtype'
    END
    
    IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'serial_number')
    AND NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'pagetypings' AND COLUMN_NAME = 'serial_no')
    BEGIN
        EXEC sp_rename 'pagetypings.serial_number', 'serial_no', 'COLUMN'
        PRINT 'Renamed serial_number to serial_no'
    END
END

-- Check if file_indexings table has is_updated column
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'file_indexings' AND COLUMN_NAME = 'is_updated')
BEGIN
    ALTER TABLE [dbo].[file_indexings] ADD [is_updated] [bit] NOT NULL DEFAULT 0
    PRINT 'Added is_updated column to file_indexings table'
END

-- Create indexes for better performance
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_pagetypings_file_indexing_id')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_pagetypings_file_indexing_id] ON [dbo].[pagetypings] ([file_indexing_id])
    PRINT 'Created index on file_indexing_id'
END

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_pagetypings_scanning_id')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_pagetypings_scanning_id] ON [dbo].[pagetypings] ([scanning_id])
    PRINT 'Created index on scanning_id'
END

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_file_indexings_is_updated')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_file_indexings_is_updated] ON [dbo].[file_indexings] ([is_updated])
    PRINT 'Created index on is_updated'
END

PRINT 'EDMS Page Typing schema update completed successfully!'
PRINT 'You can now use the PageType More functionality with is_updated = 1 files.'