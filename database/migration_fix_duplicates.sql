-- Migration script to fix duplicate enrollments issue
-- This script adds a UNIQUE constraint to prevent duplicate enrollments

-- First, identify and keep only the latest enrollment for each user-course pair
-- Delete all but the latest enrollment for each user-course combination
DELETE FROM enrollments 
WHERE EnrollmentID NOT IN (
    SELECT MAX(EnrollmentID) FROM (
        SELECT CourseID, UserID, MAX(EnrollmentID) as MaxID
        FROM enrollments
        GROUP BY CourseID, UserID
    ) as latest_enrollments
    WHERE enrollments.EnrollmentID = latest_enrollments.MaxID
);

-- Add UNIQUE constraint to prevent future duplicates
ALTER TABLE enrollments 
ADD CONSTRAINT unique_user_course UNIQUE KEY (UserID, CourseID);
