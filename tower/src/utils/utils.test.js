import {format} from "./date";
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

describe('grid.getLinesTemplate', () => {
    it.each([
        {lines: [], template: 'auto'},
        {lines: ['one'], template: '[line-one-start] auto [line-one-end]'},
        {lines: ['one', 'two'], template: '[line-one-start] auto [line-one-end line-two-start] auto [line-two-end]'},
    ])('generate CSS grid-template-columns by given column names', ({lines, template}) => {
        expect(getLinesTemplate(lines)).toEqual(template);
    })
})
