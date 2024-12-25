CREATE TABLE `table_name` (
  `ID` int(255) NOT NULL,
  `Time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

ALTER TABLE `table_name`
  ADD PRIMARY KEY (`ID`);

ALTER TABLE `table_name`
  MODIFY `ID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;