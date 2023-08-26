export function getDates(currentDate: Date, endDate: Date): Array<string>
{
    const dates = [];
    while (currentDate < endDate) {
        dates.push(format(currentDate));
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return dates;
}

export function format(date: Date): string {
    const month = date.getMonth() + 1;
    const day = date.getDate();
    return `${date.getFullYear()}-${month < 10 ? '0' + month : month}-${day < 10 ? '0' + day : day}`;
}

export function shiftDate(date: Date, shift: number): Date
{
    const shiftedDate = new Date(date.getTime());
    shiftedDate.setDate(date.getDate() + shift);
    return shiftedDate;
}
