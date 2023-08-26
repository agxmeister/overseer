import {format, shiftDate} from "./date";
import {describe} from "node:test";
import {getLinesTemplate} from "./grid";

describe('date.format', () => {
    it.each([
        '2023-07-20',
        '2023-07-02',
        '2023-11-11',
    ])('convert date to string', (date) => {
        expect(format(new Date(date))).toEqual(date);
    })
})

describe('date.shiftDate', () => {
    it.each([
        {date: new Date('2023-01-01'), shift: 2, shiftedDate: new Date('2023-01-03')},
        {date: new Date('2023-11-03'), shift: -2, shiftedDate: new Date('2023-11-01')},
        {date: new Date('2023-01-30'), shift: 2, shiftedDate: new Date('2023-02-01')},
        {date: new Date('2023-11-01'), shift: -2, shiftedDate: new Date('2023-10-30')},
    ])('shift date', ({date, shift, shiftedDate}) => {
        expect(format(shiftDate(date, shift))).toEqual(format(shiftedDate));
    })
})

describe('grid.getLinesTemplate', () => {
    it.each([
        {lines: [], template: 'auto'},
        {lines: [], size: "10em", template: 'auto'},
        {lines: ['one'], template: '[line-one-start] auto [line-one-end]'},
        {lines: ['one', 'two'], template: '[line-one-start] auto [line-one-end line-two-start] auto [line-two-end]'},
        {lines: ['one'], size: "10em", template: '[line-one-start] 10em [line-one-end]'},
    ])('generate CSS grid-template-columns by given column names', ({lines, size, template}) => {
        size ?
            expect(getLinesTemplate(lines, size)).toEqual(template) :
            expect(getLinesTemplate(lines)).toEqual(template);
    })
})
