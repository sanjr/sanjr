-- 11: blood group of the student (printed on the back of the PCID card).
-- Optional; one of A+ A- B+ B- AB+ AB- O+ O- (checked by the website).
ALTER TABLE students ADD COLUMN blood_group VARCHAR(3) NULL AFTER gender;
